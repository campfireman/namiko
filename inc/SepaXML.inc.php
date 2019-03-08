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

	static private $doc = null;
	private $GrpHdr = null;
	private $PmtTpInf = null;
	private $PmtInf = null;
	public $errorDetails = null;

	public function createHdr () {
		self::$doc = new DOMDocument('1.0', 'utf-8');
		self::$doc->formatOutput = true;

		$root = self::$doc->createElementNS('urn:iso:std:iso:20022:tech:xsd:pain.008.003.02', 'Document');
		self::$doc->appendChild($root);
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'urn:iso:std:iso:20022:tech:xsd:pain.008.003.02 pain.008.003.02.xsd');

		$CstmrDrctDbtInitn = self::$doc->createElement('CstmrDrctDbtInitn');
		$CstmrDrctDbtInitn = $root->appendChild($CstmrDrctDbtInitn);
		
		// Layer 1: CstmrDrctDbtInitn
		$this->GrpHdr = self::$doc->createElement('GrpHdr');
		$this->GrpHdr = $CstmrDrctDbtInitn->appendChild($this->GrpHdr);

			// Layer 2: GrpHdr
			$this->GrpHdr->appendChild(self::$doc->createElement('MsgId', $this->msgID));
			$this->GrpHdr->appendChild(self::$doc->createElement('CreDtTm', $this->creDtTm));

		// Layer 1: CstmrDrctDbtInitn
		$this->PmtInf = self::$doc->createElement('PmtInf');
		$this->PmtInf = $CstmrDrctDbtInitn->appendChild($this->PmtInf);

			// Layer 2: PmtInf
			$this->PmtInf->appendChild(self::$doc->createElement('PmtInfId', $this->pymntID));
			$this->PmtInf->appendChild(self::$doc->createElement('PmtMtd', $this->pymntMtd));
			$this->PmtInf->appendChild(self::$doc->createElement('BtchBookg', $this->BtchBookg));
			$this->PmtTpInf = self::$doc->createElement('PmtTpInf');
			$this->PmtTpInf = $this->PmtInf->appendChild($this->PmtTpInf);

				// Layer 3: PmtTpInf
				$SvcLvl = self::$doc->createElement('SvcLvl');
				$SvcLvl = $this->PmtTpInf->appendChild($SvcLvl);

					// Layer 4: SvcLvl
					$SvcLvl->appendChild(self::$doc->createElement('Cd', $this->SvcLvl));

				$LclInstrm = self::$doc->createElement('LclInstrm');
				$LclInstrm = $this->PmtTpInf->appendChild($LclInstrm);

					// Level 4: LclInstrm
					$LclInstrm->appendChild(self::$doc->createElement('Cd', $this->LclInstrm));

				$this->PmtTpInf->appendChild(self::$doc->createElement('SeqTp', $this->SeqTp));

			$this->PmtInf->appendChild(self::$doc->createElement('ReqdColltnDt', $this->collectionDt));
			$Cdtr = self::$doc->createElement('Cdtr');
			$Cdtr = $this->PmtInf->appendChild($Cdtr);

				// Level 4: Cdtr
				$Cdtr->appendChild(self::$doc->createElement('Nm', $this->myEntity));

			$CdtrAcct = self::$doc->createElement('CdtrAcct');
			$CdtrAcct = $this->PmtInf->appendChild($CdtrAcct);

				// Level 4: CdtrAcct
				$Id = self::$doc->createElement('Id');
				$Id = $CdtrAcct->appendChild($Id);

					// Level 5: Id
					$Id->appendChild(self::$doc->createElement('IBAN', $this->myIBAN));

			$CdtrAgt = self::$doc->createElement('CdtrAgt');
			$CdtrAgt = $this->PmtInf->appendChild($CdtrAgt);

				// Level 4: CdtrAgt
				$FinInstnId = self::$doc->createElement('FinInstnId');
				$FinInstnId = $CdtrAgt->appendChild($FinInstnId);

					// Level 5: FinInstId
					$FinInstnId->appendChild(self::$doc->createElement('BIC', $this->myBIC));

			$this->PmtInf->appendChild(self::$doc->createElement('ChrgBr', $this->ChrgBr));
			$CdtrSchmeId = self::$doc->createElement('CdtrSchmeId');
			$CdtrSchmeId = $this->PmtInf->appendChild($CdtrSchmeId);

				// Level 4: CdtrSchmeId
				$Id = self::$doc->createElement('Id');
				$Id = $CdtrSchmeId->appendChild($Id);

					// Level 5: Id
					$PrvtId = self::$doc->createElement('PrvtId');
					$PrvtId = $Id->appendChild($PrvtId);

						// Level 6: PrvtId
						$Othr = self::$doc->createElement('Othr');
						$Othr = $PrvtId->appendChild($Othr);

							// Level 7: Othr
							$Othr->appendChild(self::$doc->createElement('Id', $this->creditorId));
							$SchmeNm = self::$doc->createElement('SchmeNm');
							$SchmeNm = $Othr->appendChild($SchmeNm);

								// Level 8: SchmeNm
								$SchmeNm->appendChild(self::$doc->createElement('Prtry', $this->Prtry));


	}

	public function appendDbtr () {
		// Layer 2: PmtInf
		$DrctDbtTxInf = self::$doc->createElement('DrctDbtTxInf');
		$DrctDbtTxInf = $this->PmtInf->appendChild($DrctDbtTxInf);

			// Layer 3: DrctDbtTxInf
			$PmtId = self::$doc->createElement('PmtId');
			$PmtId = $DrctDbtTxInf->appendChild($PmtId);
				
				// Layer 4: PmtId
				$PmtId->appendChild(self::$doc->createElement('EndToEndId', $this->txID));

			$InstdAmt = self::$doc->createElement('InstdAmt', $this->InstdAmt);
			$InstdAmt->setAttribute('Ccy', $this->Ccy);
			$DrctDbtTxInf->appendChild($InstdAmt);

			$DrctDbtTx = self::$doc->createElement('DrctDbtTx');
			$DrctDbtTx = $DrctDbtTxInf->appendChild($DrctDbtTx);

				// Layer 4: DrctDbtTx
				$MndtRltdInf = self::$doc->createElement('MndtRltdInf');
				$MndtRltdInf = $DrctDbtTx->appendChild($MndtRltdInf);

					// Layer 5: MndtRltdInf
					$MndtRltdInf->appendChild(self::$doc->createElement('MndtId', $this->mid));
					$MndtRltdInf->appendChild(self::$doc->createElement('DtOfSgntr', $this->signed));
					$MndtRltdInf->appendChild(self::$doc->createElement('AmdmntInd', $this->AmdmntInd));

			$DbtrAgt = self::$doc->createElement('DbtrAgt');
			$DbtrAgt = $DrctDbtTxInf->appendChild($DbtrAgt);

				// Layer 4: DbtrAgt
				$FinInstnId = self::$doc->createElement('FinInstnId');
				$FinInstnId = $DbtrAgt->appendChild($FinInstnId);
					
					// Layer 5: FinInstnId
					$FinInstnId->appendChild(self::$doc->createElement('BIC', $this->BIC));

			$Dbtr = self::$doc->createElement('Dbtr');
			$Dbtr = $DrctDbtTxInf->appendChild($Dbtr);
				
				// Layer 4: Dbtr
				$Dbtr->appendChild(self::$doc->createElement('Nm', $this->account_holder));

			$DbtrAcct = self::$doc->createElement('DbtrAcct');
			$DbtrAcct = $DrctDbtTxInf->appendChild($DbtrAcct);

				// Layer 4: DbtrAcct
				$Id = self::$doc->createElement('Id');
				$Id = $DbtrAcct->appendChild($Id);
					
					// Layer 5: Id
					$Id->appendChild(self::$doc->createElement('IBAN', $this->IBAN));

			$RmtInf = self::$doc->createElement('RmtInf');
			$RmtInf = $DrctDbtTxInf->appendChild($RmtInf);
				
				// Layer 4: RmtInf
				$RmtInf->appendChild(self::$doc->createElement('Ustrd', $this->RmtInf));

	}

	public function createDoc () {
		if ($this->numberTx <= 1) {
			self::$doc->getElementsByTagName("BtchBookg")->item(0)->nodeValue = 'false';
		}
		// Layer 2: GrpHdr
		$this->GrpHdr->appendChild(self::$doc->createElement('NbOfTxs', $this->numberTx));
		$this->GrpHdr->appendChild(self::$doc->createElement('CtrlSum', sprintf("%01.2f", ($this->totalTx))));
		$InitgPty = self::$doc->createElement('InitgPty');
		$InitgPty = $this->GrpHdr->appendChild($InitgPty);
			
			// Layer 3: InitgPty
			$InitgPty->appendChild(self::$doc->createElement('Nm', $this->InitgPty));

		// Layer 2: PmtInf
		$CtrlSum = self::$doc->createElement('CtrlSum', sprintf("%01.2f", ($this->totalTx)));
		$CtrlSum = $this->PmtInf->insertBefore($CtrlSum, $this->PmtTpInf);
		$NbOfTxs = self::$doc->createElement('NbOfTxs', $this->numberTx);
		$NbOfTxs = $this->PmtInf->insertBefore($NbOfTxs, $CtrlSum);

		# Save file
		self::$doc->save($this->filename);
		
		# Validate file
		$validator = new DomValidator;
		$validated = $validator->validateFeeds($this->filename);

		if ($validated) {
		  return true;
		} else {
		  $this->errorDetails = $validator->displayErrors();
		}
	}
}
?>