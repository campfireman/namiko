<?php
require_once("DOMValidator.inc.php");

class SepaXML {
	# general
	public $filename;
	public $pymntMtd = 'DD';
	public $SeqTp = 'RCUR';
	public $Prtry = 'SEPA';
	public $LclInstrm = 'CORE';
	public $SvcLvl = 'SEPA';
	public $ChrgBr = 'SLEV';
	public $Ccy = 'EUR';
	public $BtchBookg = 'true';

	# hdr publics
	public $collectionDt;
	public $msgID;
	public $creDtTm;
	public $InitgPty;
	public $myEntity;
	public $myIBAN;
	public $myBIC;
	public $creditorId;

	# dbtr publics
	public $account_holder;
	public $IBAN;
	public $BIC;
	public $mid;
	public $signed;
	public $InstdAmt;
	public $txID;
	public $RmtInf;
	public $AmdmntInd = 'false';

	# Control
	public $numberTx = 0;
	public $totalTx = 0;

	private $doc;
	private $GrpHdr;
	private $PmtTpInf;
	private $PmtInf;
	public $errorDetails;

	public function createHdr () {
		$this->doc = new DOMDocument('1.0', 'utf-8');
		$this->doc->formatOutput = true;

		$root = $this->doc->createElementNS('urn:iso:std:iso:20022:tech:xsd:pain.008.003.02', 'Document');
		$this->doc->appendChild($root);
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'urn:iso:std:iso:20022:tech:xsd:pain.008.003.02 pain.008.003.02.xsd');

		$CstmrDrctDbtInitn = $this->doc->createElement('CstmrDrctDbtInitn');
		$CstmrDrctDbtInitn = $root->appendChild($CstmrDrctDbtInitn);
		
		// Layer 1: CstmrDrctDbtInitn
		$this->GrpHdr = $this->doc->createElement('GrpHdr');
		$this->GrpHdr = $CstmrDrctDbtInitn->appendChild($this->GrpHdr);

			// Layer 2: GrpHdr
			$this->GrpHdr->appendChild($this->doc->createElement('MsgId', $this->msgID));
			$this->GrpHdr->appendChild($this->doc->createElement('CreDtTm', $this->creDtTm));

		// Layer 1: CstmrDrctDbtInitn
		$this->PmtInf = $this->doc->createElement('PmtInf');
		$this->PmtInf = $CstmrDrctDbtInitn->appendChild($this->PmtInf);

			// Layer 2: PmtInf
			$this->PmtInf->appendChild($this->doc->createElement('PmtInfId', $this->pymntID));
			$this->PmtInf->appendChild($this->doc->createElement('PmtMtd', $this->pymntMtd));
			$this->PmtInf->appendChild($this->doc->createElement('BtchBookg', $this->BtchBookg));
			$this->PmtTpInf = $this->doc->createElement('PmtTpInf');
			$this->PmtTpInf = $this->PmtInf->appendChild($this->PmtTpInf);

				// Layer 3: PmtTpInf
				$SvcLvl = $this->doc->createElement('SvcLvl');
				$SvcLvl = $this->PmtTpInf->appendChild($SvcLvl);

					// Layer 4: SvcLvl
					$SvcLvl->appendChild($this->doc->createElement('Cd', $this->SvcLvl));

				$LclInstrm = $this->doc->createElement('LclInstrm');
				$LclInstrm = $this->PmtTpInf->appendChild($LclInstrm);

					// Level 4: LclInstrm
					$LclInstrm->appendChild($this->doc->createElement('Cd', $this->LclInstrm));

				$this->PmtTpInf->appendChild($this->doc->createElement('SeqTp', $this->SeqTp));

			$this->PmtInf->appendChild($this->doc->createElement('ReqdColltnDt', $this->collectionDt));
			$Cdtr = $this->doc->createElement('Cdtr');
			$Cdtr = $this->PmtInf->appendChild($Cdtr);

				// Level 4: Cdtr
				$Cdtr->appendChild($this->doc->createElement('Nm', $this->myEntity));

			$CdtrAcct = $this->doc->createElement('CdtrAcct');
			$CdtrAcct = $this->PmtInf->appendChild($CdtrAcct);

				// Level 4: CdtrAcct
				$Id = $this->doc->createElement('Id');
				$Id = $CdtrAcct->appendChild($Id);

					// Level 5: Id
					$Id->appendChild($this->doc->createElement('IBAN', $this->myIBAN));

			$CdtrAgt = $this->doc->createElement('CdtrAgt');
			$CdtrAgt = $this->PmtInf->appendChild($CdtrAgt);

				// Level 4: CdtrAgt
				$FinInstnId = $this->doc->createElement('FinInstnId');
				$FinInstnId = $CdtrAgt->appendChild($FinInstnId);

					// Level 5: FinInstId
					$FinInstnId->appendChild($this->doc->createElement('BIC', $this->myBIC));

			$this->PmtInf->appendChild($this->doc->createElement('ChrgBr', $this->ChrgBr));
			$CdtrSchmeId = $this->doc->createElement('CdtrSchmeId');
			$CdtrSchmeId = $this->PmtInf->appendChild($CdtrSchmeId);

				// Level 4: CdtrSchmeId
				$Id = $this->doc->createElement('Id');
				$Id = $CdtrSchmeId->appendChild($Id);

					// Level 5: Id
					$PrvtId = $this->doc->createElement('PrvtId');
					$PrvtId = $Id->appendChild($PrvtId);

						// Level 6: PrvtId
						$Othr = $this->doc->createElement('Othr');
						$Othr = $PrvtId->appendChild($Othr);

							// Level 7: Othr
							$Othr->appendChild($this->doc->createElement('Id', $this->creditorId));
							$SchmeNm = $this->doc->createElement('SchmeNm');
							$SchmeNm = $Othr->appendChild($SchmeNm);

								// Level 8: SchmeNm
								$SchmeNm->appendChild($this->doc->createElement('Prtry', $this->Prtry));


	}

	public function appendDbtr () {
		// Layer 2: PmtInf
		$DrctDbtTxInf = $this->doc->createElement('DrctDbtTxInf');
		$DrctDbtTxInf = $this->PmtInf->appendChild($DrctDbtTxInf);

			// Layer 3: DrctDbtTxInf
			$PmtId = $this->doc->createElement('PmtId');
			$PmtId = $DrctDbtTxInf->appendChild($PmtId);
				
				// Layer 4: PmtId
				$PmtId->appendChild($this->doc->createElement('EndToEndId', $this->txID));

			$InstdAmt = $this->doc->createElement('InstdAmt', $this->InstdAmt);
			$InstdAmt->setAttribute('Ccy', $this->Ccy);
			$DrctDbtTxInf->appendChild($InstdAmt);

			$DrctDbtTx = $this->doc->createElement('DrctDbtTx');
			$DrctDbtTx = $DrctDbtTxInf->appendChild($DrctDbtTx);

				// Layer 4: DrctDbtTx
				$MndtRltdInf = $this->doc->createElement('MndtRltdInf');
				$MndtRltdInf = $DrctDbtTx->appendChild($MndtRltdInf);

					// Layer 5: MndtRltdInf
					$MndtRltdInf->appendChild($this->doc->createElement('MndtId', $this->mid));
					$MndtRltdInf->appendChild($this->doc->createElement('DtOfSgntr', $this->signed));
					$MndtRltdInf->appendChild($this->doc->createElement('AmdmntInd', $this->AmdmntInd));

			$DbtrAgt = $this->doc->createElement('DbtrAgt');
			$DbtrAgt = $DrctDbtTxInf->appendChild($DbtrAgt);

				// Layer 4: DbtrAgt
				$FinInstnId = $this->doc->createElement('FinInstnId');
				$FinInstnId = $DbtrAgt->appendChild($FinInstnId);
					
					// Layer 5: FinInstnId
					$FinInstnId->appendChild($this->doc->createElement('BIC', $this->BIC));

			$Dbtr = $this->doc->createElement('Dbtr');
			$Dbtr = $DrctDbtTxInf->appendChild($Dbtr);
				
				// Layer 4: Dbtr
				$Dbtr->appendChild($this->doc->createElement('Nm', $this->account_holder));

			$DbtrAcct = $this->doc->createElement('DbtrAcct');
			$DbtrAcct = $DrctDbtTxInf->appendChild($DbtrAcct);

				// Layer 4: DbtrAcct
				$Id = $this->doc->createElement('Id');
				$Id = $DbtrAcct->appendChild($Id);
					
					// Layer 5: Id
					$Id->appendChild($this->doc->createElement('IBAN', $this->IBAN));

			$RmtInf = $this->doc->createElement('RmtInf');
			$RmtInf = $DrctDbtTxInf->appendChild($RmtInf);
				
				// Layer 4: RmtInf
				$RmtInf->appendChild($this->doc->createElement('Ustrd', $this->RmtInf));

	}

	public function createDoc () {
		if ($this->numberTx <= 1) {
			$this->doc->getElementsByTagName("BtchBookg")->item(0)->nodeValue = 'false';
		}
		// Layer 2: GrpHdr
		$this->GrpHdr->appendChild($this->doc->createElement('NbOfTxs', $this->numberTx));
		$this->GrpHdr->appendChild($this->doc->createElement('CtrlSum', sprintf("%01.2f", ($this->totalTx))));
		$InitgPty = $this->doc->createElement('InitgPty');
		$InitgPty = $this->GrpHdr->appendChild($InitgPty);
			
			// Layer 3: InitgPty
			$InitgPty->appendChild($this->doc->createElement('Nm', $this->InitgPty));

		// Layer 2: PmtInf
		$CtrlSum = $this->doc->createElement('CtrlSum', sprintf("%01.2f", ($this->totalTx)));
		$CtrlSum = $this->PmtInf->insertBefore($CtrlSum, $this->PmtTpInf);
		$NbOfTxs = $this->doc->createElement('NbOfTxs', $this->numberTx);
		$NbOfTxs = $this->PmtInf->insertBefore($NbOfTxs, $CtrlSum);

		# Save file
		$this->doc->save($this->filename);
		
		# Validate file
		$validator = new DomValidator;
		$validated = $validator->validateFeeds($this->filename);

		if (!$validated) {
			throw new Exception(json_encode($validator->displayErrors()));
		}
	}
}
?>