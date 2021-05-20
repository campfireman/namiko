<?php
require_once "config.inc.php";
require_once "functions.inc.php";
require_once "SepaXML.inc.php";
require_once "Mail.inc.php";

class SEPAprocedure
{
    // privates
    private $xml;
    private $mails = [];
    private $pdo;
    private $sid;
    private $date;
    private $time;

    // publics
    public $filename;
    public $pymntID;

    /**
     * create the header of the xml document
     *
     * @param obj pdo connection
     * @param int $uid
     * @param string english format YYYY-mm-dd
     * @param string name of initiating entity
     * @param string IBAN
     * @param string BIC
     * @param string Creditor ID
     * @param string name of initiator
     */
    public function __construct($pdo, $creator, $collectionDt, $myEntity, $myIBAN, $myBIC, $creditorId, $InitgPty)
    {
        $this->xml = new SepaXML();
        $this->pdo = $pdo;

        $statement = $this->pdo->prepare("INSERT INTO sepaDocs (creator) VALUES (:creator)");
        $result = $statement->execute(array('creator' => $creator));

        if ($result) {
            $statement = $this->pdo->prepare("SELECT sid FROM sepaDocs WHERE created_at = (SELECT MAX(created_at) FROM sepaDocs)");
            $result = $statement->execute();
            $sepaDoc = $statement->fetch();

            $this->xml->collectionDt = $collectionDt;
            $this->sid = $sepaDoc['sid'];
            $this->date = date('Ymd');
            $this->time = date('His');
            $this->xml->msgID = $myBIC . 'SID' . $this->sid . '-' . $this->date . $this->time;
            $this->xml->creDtTm = date('Y-m-d') . 'T' . date('H:i:s');
            $this->xml->InitgPty = $InitgPty;
            $this->xml->pymntID = 'SID' . $this->sid . 'D' . $this->date . 'T' . $this->time;
            $this->xml->filename = dirname(dirname(__FILE__)) . '/sepa/' . $this->xml->pymntID . '.xml';
            $this->xml->myEntity = $myEntity;
            $this->xml->myIBAN = $myIBAN;
            $this->xml->myBIC = $myBIC;
            $this->xml->creditorId = $creditorId;

            $this->xml->createHdr();
        } else {
            throw new Exception(json_encode($statement->errorInfo()));

        }
    }

    /**
     * @param  multidimensional Array of all the necessary data
     * first level: uid
     * second level:
     * @return void
     */
    public function insertTx($transactions, $type = null)
    {
        $numberTx = 0;
        $totalTx = 0;

        foreach ($transactions as $uid => $tx) {
            $numberTx++;
            $first_name = $tx['first_name'];
            $last_name = $tx['last_name'];
            $email = $tx['email'];
            $this->xml->account_holder = $tx['account_holder'];
            $this->xml->IBAN = $tx['IBAN'];
            $this->xml->BIC = $tx['BIC'];
            $this->xml->mid = $tx['mid'];
            $this->xml->signed = $tx['signed'];
            $this->xml->InstdAmt = $tx['instdAmt'];
            $this->xml->RmtInf = $tx['rmtInf'];
            $totalTx += $this->xml->InstdAmt;

            $statement = $this->pdo->prepare("INSERT INTO payments (uid, reference, amount) VALUES (:uid, :reference, :amount)");
            $result = $statement->execute(array('uid' => $uid, 'reference' => $this->sid, 'amount' => $this->xml->InstdAmt));

            if (!$result) {
                throw new Exception(json_encode($statement->errorInfo()));
            }

            $statement = $this->pdo->prepare("SELECT pay_id FROM payments WHERE uid = '$uid' AND reference = '$this->sid'");
            $result = $statement->execute();

            if (!$result) {
                throw new Exception(json_encode($statement->errorInfo()));
            }

            $pay_id = $statement->fetch();

            $this->xml->txID = 'ID' . $pay_id['pay_id'] . 'D' . $this->date . 'T' . $this->time;

            if (isset($type)) {
                $statement = $this->pdo->prepare("INSERT INTO " . $type . " (uid, pay_id) VALUES (:uid, :pay_id)");
                $result = $statement->execute(array('uid' => $uid, 'pay_id' => $pay_id['pay_id']));
            }

            if (!$result) {
                throw new Exception(json_encode($statement->errorInfo()));
            }

            $date = new DateTime($this->xml->collectionDt);
            $text = '
			<h1>Moin, ' . htmlspecialchars($first_name) . '!</h1>
			<p>Wir ziehen einen Betrag von ' . sprintf("%01.2f", ($this->xml->InstdAmt)) . ' EUR mit der SEPA-Lastschrift zum Mandat mit der Referenznummer ' . $this->xml->mid . ' zu der Gläubiger-Identifikationsnummer ' . $this->xml->creditorId . ' von Deinem Konto IBAN ' . maskString($this->xml->IBAN, 8) . ' bei BIC ' . $this->xml->BIC . ' zum Fälligkeitstag ' . $date->format('d.m.Y') . ' ein.';

            $this->mails[$uid]['email'] = $email;
            $this->mails[$uid]['recipient'] = $first_name . ' ' . $last_name;
            $this->mails[$uid]['subject'] = $tx['rmtInf'];
            $this->mails[$uid]['text'] = $text;

            $this->xml->appendDbtr();
        }

        $this->xml->numberTx = $numberTx;
        $this->xml->totalTx = $totalTx;
    }

    /**
     * create and save the sepa xml file
     * @return void
     */
    public function create()
    {
        $pymntID = $this->xml->pymntID;
        $statement = $this->pdo->prepare("UPDATE sepaDocs SET PmtInfId = '$pymntID' WHERE sid = '$this->sid'");
        $result = $statement->execute();

        if (!$result) {
            throw new Exception(json_encode($statement->errorInfo()));
        }
        $this->xml->createdoc();
    }

    /**
     * @param  stirng smtp hostname
     * @param  string smtp username
     * @param  string smtp server password
     * @param  string the reply to and from email
     * @param  string name of sender
     * @return void
     */
    public function notify($smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity)
    {
        $mail = new Mail($smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity);

        if ($mail->sendBatch($this->mails)) {
            throw new Exception("Einige Benachrichtigungen konnten nicht versendet werden: " . $mail->getMailErrors());
        }
    }

    /**
     * initiates download prompt
     * @return void
     */
    public function startDownload()
    {
        $file = $this->xml->pymntID . '.xml';
        $path = dirname(dirname(__FILE__)) . '/sepa/' . $file;

        header('Content-Description: File Transfer');
        header('Content-Type: "text/xml"; charset="utf8";');
        header('Content-disposition: attachment; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile($path);
        exit();
    }
}
