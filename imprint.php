<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
//ini_set('display_errors', 1);

if (is_checked_in()) {
	$user = check_user();
}

include("templates/header.inc.php");
include("templates/nav.inc.php");
?>


<div class="sizer spacer">

	<p>Unser Webspace wird bereitgestellt durch <a href="https://www.do.de/?aff=5Q4ytXcHmb" title="do.de" target="_new">do.de -Domain-Offensive</a> mit 100% Ökostrom.</p>
	<p>&nbsp;</p>
	<h3>Angaben gemäß § 5 TMG:</h3>
	<p>&nbsp;<br>
	namiko Hannover e.V.<br>
	<br>
	Windthorstr. 13<br>
	30167 Hannover<br>
	Deutschland</p>
	<h3>Kontakt:</h3>
	<p>&nbsp;<br>
	Telefon:<br>
	E-Mail: kontakt@namiko.org</p>
	<p>Aufsichtsbehörde: Staatliches Gewerbeaufsichtsamt Hannover<br>
	Umsatzsteuer-Identifikationsnummer gemäß §27 a Umsatzsteuergesetz:<br>
	</p>
	<p>Landratsamt Hannover<br>
	&nbsp;</p>
	<h3>Verantwortlich<br>
	für den Inhalt nach § 55 Abs. 2 RStV:</h3>
	<p>&nbsp;<br>
	Ture Claußen<br>
	Darwinstr. 17<br>
	30165 Hannover<br>
	Hannover</p>
	<p>Wir sind nicht bereit oder verpflichtet, an<br>
	Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>
	<h3>Haftung<br>
	für Inhalte</h3>
	<p>&nbsp;<br>
	Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für<br>
	eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis<br>
	10 TMG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde<br>
	Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige<br>
	Tätigkeit hinweisen.</p>
	<p>Verpflichtungen zur Entfernung oder Sperrung der Nutzung von<br>
	Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche<br>
	Haftung ist jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich.<br>
	Bei Bekanntwerden von entsprechenden Rechtsverletzungen werden wir diese Inhalte umgehend<br>
	entfernen.</p>
	<h3>Haftung für Links</h3>
	<p>&nbsp;<br>
	Unser Angebot enthält Links zu externen<br>
	Webseiten Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese<br>
	fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist<br>
	stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich. Die verlinkten Seiten wurden zum<br>
	Zeitpunkt der Verlinkung auf mögliche Rechtsverstöße überprüft.<br>
	Rechtswidrige Inhalte waren zum Zeitpunkt der Verlinkung nicht erkennbar.</p>
	<p>Eine permanente<br>
	inhaltliche Kontrolle der verlinkten Seiten ist jedoch ohne konkrete Anhaltspunkte einer Rechtsverletzung<br>
	nicht zumutbar. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links umgehend<br>
	entfernen.</p>
	<h3>Urheberrecht</h3>
	<p>&nbsp;<br>
	Die durch die Seitenbetreiber erstellten Inhalte und Werke auf<br>
	diesen Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung,<br>
	Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen<br>
	der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers. Downloads und Kopien dieser Seite<br>
	sind nur für den privaten, nicht kommerziellen Gebrauch gestattet.</p>
	<p>Soweit die Inhalte auf<br>
	dieser Seite nicht vom Betreiber erstellt wurden, werden die Urheberrechte Dritter beachtet. Insbesondere<br>
	werden Inhalte Dritter als solche gekennzeichnet. Sollten Sie trotzdem auf eine Urheberrechtsverletzung<br>
	aufmerksam werden, bitten wir um einen entsprechenden Hinweis. Bei Bekanntwerden von<br>
	Rechtsverletzungen werden wir derartige Inhalte umgehend entfernen.</p>

</div>


<?php 
include("templates/footer.inc.php")
?>