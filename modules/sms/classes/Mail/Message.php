<?php
/**
 * 
 */
namespace zesk\SMS\Mail;

use zesk\Exception_Unimplemented;

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/sms/classes/sms.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2007, Market Acumen, Inc.
 */
class Message extends \zesk\SMS\Message {
	public static function select_options() {
		return array(
			"phonenumber@tmomail.net" => "T-Mobile",
			"phonenumber@vmobl.com" => "Virgin Mobile",
			"phonenumber@cingularme.com" => "Cingular",
			"phonenumber@messaging.sprintpcs.com" => "Sprint",
			"phonenumber@vtext.com" => "Verizon",
			"phonenumber@messaging.nextel.com" => "Nextel",
			"phonenumber@txt.att.net" => "AT & T"
		);
	}
	function send() {
		throw new Exception_Unimplemented(__METHOD__);
	}
}

/* See this: https://martinfitzpatrick.name/list-of-email-to-sms-gateways/ */

/*

MetroPCS: phonenumber@mymetropcs.com
-Mobile Sidekick I & II

phonenumber@tmail.com

10digitphonenumber@mobile.mycingular.com

<Full Number including prefix>@t-mobile-sms.de

Example: 0176563684@t-mobile-sms.de will send an SMS to 0-176-563684.

Obviously only works for German T-Mobile (D1) handys!


      Aliant (NBTel, MTT, NewTel, and Island Tel) (from: 11, msg: 140, total: 140)
      Enter your phone number. Message is sent to number@chat.wirefree.ca

      Alltel (from: 50, msg: 116, total: 116)
      Enter your phone number. Goes to number@message.alltel.com.

      Ameritech (ACSWireless) (from: 120, msg: 120, total: 120)
      Enter your phone number. Goes to number@paging.acswireless.com

      Arch Wireless (from: 15, msg: 240, total: 240)
      Enter your phone number. Sent via http://www.arch.com/message/ (assumes blank PIN)

      AU by KDDI (from: 20, msg: 10000, total: 10000)
      Enter your phone number. Goes to username@ezweb.ne.jp

      BeeLine GSM (from: 50, msg: 255, total: 255)
      Enter your phone number. Goes to number@sms.beemail.ru

      Bell Mobility Canada (from: 20, msg: 120, total: 120)
      Enter your phone number, including the 1 prefix. Goes to number@txt.bellmobility.ca

      Bellsouth (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@bellsouth.cl

      BellSouth Mobility (from: 15, msg: 160, total: 160)
      Enter your phone number. Goes to number@blsdcs.net

      Blue Sky Frog (from: 30, msg: 120, total: 120)
      Enter your phone number. Goes to number@blueskyfrog.com

      Boost (from: 30, msg: 120, total: 120)
      Enter your phone number. Goes to number@myboostmobile.com

      Cellular South (from: 50, msg: 155, total: 155)
      Enter your phone number. Messages are sent to number@csouth1.com

      CellularOne (Dobson) (from: 20, msg: 120, total: 120)
      Enter your phone number. Goes to number@mobile.celloneusa.com

      CellularOne West (from: 20, msg: 120, total: 120)
      Enter your phone number. Goes to number@mycellone.com

      Centennial Wireless (from: 10, msg: 110, total: 110)
      Enter your phone number. Sent via http://www.centennialwireless.com

      Cincinnati Bell (from: 20, msg: 50, total: 50)
      Enter your phone number. Goes to number@gocbw.com

      Cingular (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@mobile.mycingular.com

      Cingular Blue (formerly AT&T Wireless) (from: 50, msg: 150, total: 150)
      Enter your phone number. Goes to number@mmode.com

      Cingular IM Plus/Bellsouth IPS (from: 100, msg: 16000, total: 16000)
      Enter 8 digit PIN or user name. Goes to @imcingular.com

      Cingular IM Plus/Bellsouth IPS Cellphones (from: 100, msg: 16000, total: 16000)
      Enter phone number. Goes to @mobile.mycingular.com

      Claro (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@clarotorpedo.com.br

      Comviq (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@sms.comviq.se

      Dutchtone/Orange-NL (from: 15, msg: 150, total: 150)
      Enter your phone number. Messages are sent to number@sms.orange.nl

      Edge Wireless (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@sms.edgewireless.com

      EinsteinPCS / Airadigm Communications (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@einsteinsms.com

      EPlus (from: 20, msg: 480, total: 480)
      Enter your phone number. Goes to number@smsmail.eplus.de.

      Estonia Mobile Telefon (from: 20, msg: 160, total: 160)
      Enter your phone number. Sent via webform.

      Fido Canada (from: 15, msg: 140, total: 140)
      Enter your phone number. Goes to number@fido.ca.

      Golden Telecom (from: 20, msg: 160, total: 160)
      Enter your phone number or nickname. Messages are sent to number@sms.goldentele.com

      Idea Cellular (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@ideacellular.net

      Kyivstar (from: 30, msg: 160, total: 160)
      Sent by addressing the message to number@sms.kyivstar.net

      LMT (from: 30, msg: 120, total: 120)
      Enter your username. Goes to username@sms.lmt.lv

      Manitoba Telecom Systems (from: 20, msg: 120, total: 120)
      10-digit phone number. Goes to @text.mtsmobility.com

      Meteor (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@sms.mymeteor.ie

      Metro PCS (from: 20, msg: 120, total: 120)
      10-digit phone number. Goes to number@mymetropcs.com

      Metrocall Pager (from: 120, msg: 120, total: 120)
      10-digit phone number. Goes to number@page.metrocall.com

      MobileOne (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@m1.com.sg

      Mobilfone (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@page.mobilfone.com

      Mobility Bermuda (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@ml.bm

      MTS Primtel (from: 20, msg: 160, total: 160)
      Enter your phone number. Sent via web gateway.

      Aliant (NBTel, MTT, NewTel, and Island Tel) (from: 11, msg: 140, total: 140)
      Enter your phone number. Message is sent to number@chat.wirefree.ca

      Netcom (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@sms.netcom.no

      Nextel (from: 50, msg: 126, total: 126)
      10-digit phone number. Goes to 10digits@messaging.nextel.com. Note: do not use dashes in your phone number.

      NPI Wireless (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to number@npiwireless.com.

      NTC (from: 20, msg: 160, total: 160)
      Enter your phone number. Sent via web gateway.

      O2 (formerly BTCellnet) (from: 20, msg: 120, total: 120)
      Enter O2 username - must be enabled first at http://www.o2.co.uk. Goes to username@o2.co.uk.

      O2 M-mail (formerly BTCellnet) (from: 20, msg: 120, total: 120)
      Enter phone number, omitting initial zero - must be enabled first by sending an SMS saying "ON" to phone number "212". Goes to +44[number]@mmail.co.uk.

      Optus (from: 20, msg: 114, total: 114)
      Enter your phone number. Goes to @optusmobile.com.au

      Orange (UK) (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to @orange.net. You will need to create a user account at orange.net first.

      Oskar (from: 20, msg: 320, total: 320)
      Enter your phone number. Goes to @mujoskar.cz

      Other (from: 15, msg: 100, total: 100)
      If your provider isn't supported directly, enter the email address that sends you a text message in phone number field. To be safe, the entire message is sent in the body of the message, and the length limit is really short. We'd prefer you give us information about your provider so we can support it directly.

      Pacific Bell Cingular (from: 20, msg: 120, total: 120)
      10-digit phone number. Goes to @mobile.mycingular.com

      Pagenet (from: 20, msg: 220, total: 240)
      10-digit phone number (or gateway and pager number separated by a period). Goes to number@pagenet.net.

      PCS Rogers (from: 20, msg: 125, total: 125)
      10-digit phone number. Goes to number@pcs.rogers.com. Requires prior registration with PCS Rogers.

      Personal Communication (Sonet) (from: 20, msg: 150, total: 150)
      Enter your phone number. Goes to sms@pcom.ru with your number in the subject line.

      Plus GSM Poland (from: 20, msg: 620, total: 620)
      10-digit phone number. Goes to number@text.plusgsm.pl.

      Powertel (from: 20, msg: 120, total: 120)
      10-digit phone number. Goes to number@ptel.net

      Primtel (from: 20, msg: 150, total: 150)
      Enter your phone number. Goes to number@sms.primtel.ru

      PSC Wireless (from: 20, msg: 150, total: 150)
      Enter your phone number. Goes to number@sms.pscel.com

      Qualcomm (from: 20, msg: 120, total: 120)
      Enter your username. Goes to username@pager.qualcomm.com

      Qwest (from: 14, msg: 100, total: 100)
      10-digit phone number. Goes to @qwestmp.com

      Safaricom (from: 15, msg: 160, total: 160)
      Goes to @safaricomsms.com

      Satelindo GSM (from: 15, msg: 160, total: 160)
      Goes to @satelindogsm.com

      SCS-900 (from: 15, msg: 160, total: 160)
      Goes to @scs-900.ru

      Simple Freedom (from: 15, msg: 160, total: 160)
      Goes to @text.simplefreedom.net

      Skytel - Alphanumeric (from: 15, msg: 240, total: 240)
      Enter your 7-digit pin number as your number and your message will be mailed to pin@skytel.com

      Smart Telecom (from: 15, msg: 160, total: 160)
      Enter your phone number. Goes to @mysmart.mymobile.ph

      Smarts GSM (from: 11, msg: 70, total: 70)
      Enter your phone number. Sent via http://www.samara-gsm.ru/scripts/smsgate.exe

      Southern Linc (from: 15, msg: 160, total: 160)
      Enter your 10-digit phone number. Goes to @page.southernlinc.com

      Sprint PCS (from: 15, msg: 160, total: 160)
      Enter your 10-digit phone number. Goes to @messaging.sprintpcs.com

      Sprint PCS - Short Mail (from: 15, msg: 1000, total: 1000)
      Enter your phone number. Goes to @sprintpcs.com

      SunCom (from: 18, msg: 110, total: 110)
      Enter your number. Email will be sent to number@tms.suncom.com.

      SureWest Communications (from: 20, msg: 200, total: 200)
      Enter your phone number. Message will be sent to number@mobile.surewest.com

      SwissCom Mobile (from: 20, msg: 10000, total: 10000)
      Enter your phone number. Message will be sent to number@bluewin.ch

      T-Mobile Germany (from: 15, msg: 160, total: 160)
      Enter your number. Email will be sent to number@T-D1-SMS.de

      T-Mobile Netherlands (from: 15, msg: 160, total: 160)
      Send "EMAIL ON" to 555 from your phone, then enter your number starting with 316. Email will be sent to number@gin.nl

      T-Mobile UK (from: 30, msg: 160, total: 160)
      Messages are sent to number@t-mobile.uk.net

      T-Mobile USA (from: 30, msg: 160, total: 160)
      Messages are sent to number@tmomail.net

      T-Mobile USA (Sidekick) (from: 30, msg: 10000, total: 10000)
      Messages are sent to username@tmail.com

      Tele2 Latvia (from: 20, msg: 160, total: 160)
      10-digit phone number. Goes to number@sms.tele2.lv.

      Telefonica Movistar (from: 20, msg: 120, total: 120)
      10-digit phone number. Goes to number@movistar.net

      Telenor (from: 20, msg: 160, total: 160)
      10-digit phone number. Goes to number@mobilpost.no.

      Telia Denmark (from: 20, msg: 160, total: 160)
      8-digit phone number. Goes to number@gsm1800.telia.dk.

      Telus Mobility (from: 30, msg: 120, total: 120)
      10-digit phone number. Goes to 10digits@msg.telus.com.

      The Phone House (from: 20, msg: 160, total: 160)
      10-digit phone number. Goes to number@sms.phonehouse.de.

      TIM (from: 30, msg: 350, total: 350)
      10-digit phone number. Goes to number@timnet.com.

      UMC (from: 10, msg: 120, total: 120)
      Sent by addressing the message to number@sms.umc.com.ua

      Unicel (from: 10, msg: 120, total: 120)
      Sent by addressing the message to number@utext.com

      US Cellular (from: , msg: 150, total: 150)
      Enter a 10 digit USCC Phone Number. Messages are sent to number@email.uscc.net

      Verizon Wireless (from: 34, msg: 140, total: 140)
      Enter your 10-digit phone number. Messages are sent via email to number@vtext.com.

      Verizon Wireless (formerly Airtouch) (from: 20, msg: 120, total: 120)
      Enter your phone number. Messages are sent to number@airtouchpaging.com. This is ONLY for former AirTouch customers. Verizon Wireless customers should use Verizon Wireless instead.

      Verizon Wireless (myairmail.com) (from: 34, msg: 140, total: 140)
      Enter your phone number. Messages are sent via to number@myairmail.com.

      Vessotel (from: 20, msg: 800, total: 800)
      Enter your phone number. Messages are sent to roumer@pager.irkutsk.ru.

      Virgin Mobile Canada (from: 20, msg: 140, total: 140)
      Enter your phone number. Messages are sent to number@vmobile.ca.

      Virgin Mobile USA (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@vmobl.com.

      Vodafone Italy (from: 20, msg: 132, total: 132)
      Enter your phone number. Messages are sent to number@sms.vodafone.it

      Vodafone Japan (Chuugoku/Western) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@n.vodafone.ne.jp

      Vodafone Japan (Hokkaido) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@d.vodafone.ne.jp

      Vodafone Japan (Hokuriko/Central North) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@r.vodafone.ne.jp

      Vodafone Japan (Kansai/West -- including Osaka) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@k.vodafone.ne.jp

      Vodafone Japan (Kanto/Koushin/East -- including Tokyo) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@t.vodafone.ne.jp

      Vodafone Japan (Kyuushu/Okinawa) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@q.vodafone.ne.jp

      Vodafone Japan (Shikoku) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@s.vodafone.ne.jp

      Vodafone Japan (Touhoku/Niigata/North) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@h.vodafone.ne.jp

      Vodafone Japan (Toukai/Central) (from: 20, msg: 160, total: 160)
      Enter your phone number. Messages are sent to number@c.vodafone.ne.jp

      Vodafone Spain (from: 20, msg: 90, total: 90)
      Enter your username. Messages are sent to username@vodafone.es

      Vodafone UK (from: 20, msg: 70, total: 90)
      Enter your username. Messages are sent to username@vodafone.net

      Voicestream (from: 15, msg: 140, total: 140)
      Enter your 10-digit phone number. Message is sent via the email gateway, since they changed their web gateway and we have not gotten it working with the new one yet.

      Weblink Wireless (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to @airmessage.net

      WellCom (from: 20, msg: 160, total: 160)
      Enter your phone number. Goes to @sms.welcome2well.com

      WyndTell (from: 20, msg: 480, total: 500)
      Enter username/phone number. Goes to @wyndtell.com

      Email2SMS Carriers

Company Name 	SMTP Format
3 Rivers Wireless	xxxxxxxxxx@voicestream.net
Airadigm Communications	1xxxxxxxxxx@einsteinsms.com
Airtouch Paging	xxxxxxxxxx@alphapage.myairmail.com
Alltel	xxxxxxxxxx@message.alltel.com
Aquis Communications Inc.	xxxxxxxxxx@aquis.com
Arch Communications Group Inc.	xxxxxxxxxx@arch.com
AT & T Wireless	xxxxxxxxxx@txt.att.net
Bell Mobility	xxxxxxxxxx@pager.mobility.com
BellSouth Cellular Corp.	xxxxxxxxxx@wireless.bellsouth.com
Blackfoot Communications	xxxxxxxxxx@pcs.blackfoot.net
Cantel	xxxxxxxxxx@email2go.com
Cellular One	xxxxxxxxxx@mycellone.net
Cellular South	xxxxxxxxxx@csouth1.com
Cingular Wireless	xxxxxxxxxx@digitaledge.acswireless.com
CorrComm, LLC	xxxxxxxxxx@corrcomm.net
Dobson Cellular Systems	xxxxxxxxxx@mobile.dobson.net
Frontier Cellular	xxxxxxxxxx@message.bam.com
GTE Paging	xxxxxxxxxx@gte.pagegate.net
MCI Paging	xxxxxxxxxx@pagemci.com
Metrocall Inc.	xxxxxxxxxx@page.metrocall.com
Midwest Wireless	xxxxxxxxxx@clearlydigital.com
Mobilecomm	xxxxxxxxxx@mobilecomm.net
Nevada Bell	1xxxxxxxxxx@pacbellpcs.net
Nextel	xxxxxxxxxx@messaging.nextel.com
NPI Wireless	xxxxxxxxxx@npiwireless.com
Pacific Bell	1xxxxxxxxxx@pacbellpcs.net
PageNet Interactive	xxxxxxxxxx@pagenetips.com
PrimeCo Personal Communications Inc.	xxxxxxxxxx@primeco.textmsg.com
Qwest Wireless	xxxxxxxxxx@qwestmp.com
RAM Paging	xxxxxxxxxx@ram-page.com
Rogers Communications	xxxxxxxxxx@pcs.rogers.com
Rural Cellular Corporation	xxxxxxxxxx@typetalk.ruralcellular.com
Satellink Communications, Inc.	xxxxxxxxxx@.pageme@satellink.net
SBC Paging	xxxxxxxxxx@paging.sbc.com
Southern Linc	xxxxxxxxxx@page.southernlinc.com
Southwestern Bell	xxxxxxxxxx@email.swbw.com
Sprint PCS	xxxxxxxxxx@messaging.sprintpcs.com
SunCom	xxxxxxxxxx@tms.suncom.com
SureWest Wireless	xxxxxxxxxx@mobile.surewest.com
T-Mobile	xxxxxxxxxx@tmomail.net
TeleTouch Communications Inc.	xxxxxxxxxx@pageme.teletouch.com
Triton PCS Inc.	xxxxxxxxxx@tms.suncom.com
TSR Wireless L.L.C.	xxxxxxxxxx@alphame.com
U.S. Cellular	xxxxxxxxxx@email.uscc.net
United States Cellular Corporation	xxxxxxxxxx@uscc.textmsg.com
USA Mobile	xxxxxxxxxx@.pager@usamobile.com
ValuePage Inc.	xxxxxxxxxx@epage.valuepage.net
Verizon Wireless	xxxxxxxxxx@vtext.com

<select id="user_mobile_phone_host" name="user[mobile_phone_host]">
<option value="">Select a service...</option>
<option value="#">USA/Canada---------------</option>
<option value="airtouch.net">Airtouch Pager</option>
<option value="wirefree.informe.ca">Aliant/NBTel</option>
<option value="message.alltel.com">Alltel</option>
<option value="paging.acswireless.com">Ameritech (ACS)</option>
<option selected="selected" value="txt.att.net">AT&T</option>
<option value="mmode.com">AT&T mmode</option>
<option value="txt.bellmobility.ca">Bell Mobility Canada</option>
<option value="bellsouth.cl">Bellsouth</option>
<option value="csouth1.com">Cellular South</option>
<option value="cwemail.com">Centennial Wireless</option>
<option value="gocbw.com">Cincinnati Bell Wireless</option>
<option value="mobile.mycingular.com">Cingular</option>
<option value="sms.comviq.se">Comviq/Tele2 (Sweden)</option>
<option value="smsmail.eplus.de">E Plus (Germany)</option>
<option value="sms.edgewireless.com">Edge Wireless</option>
<option value="fido.ca">Fido Canada</option>
<option value="mms.longlines.com">LongLines Wireless</option>
<option value="mymetropcs.com">Metro PCS</option>
<option value="messaging.nextel.com">Nextel</option>
<option value="sms.nex-techwireless.com">Nex-tech Wireless</option>
<option value="pager.qualcomm.com">Qualcomm</option>
<option value="qwestmp.com">Qwest</option>
<option value="pcs.rogers.com">Rogers Wireless</option>
<option value="email.skytel.com">Skytel Pager</option>
<option value="messaging.sprintpcs.com">Sprint PCS</option>
<option value="tms.suncom.com">Suncom</option>
<option value="tmomail.net">T-Mobile USA</option>
<option value="msg.telus.com">Telus Mobility</option>
<option value="utext.com">Unicel</option>
<option value="email.uscc.net">US Cellular</option>
<option value="vtext.com">Verizon</option>
<option value="voicestream.net">Voicestream</option>
<option value="vmobile.ca">Virgin Mobile Canada</option>
<option value="vmobl.com">Virgin Mobile USA</option>
<option value="#">Elsewhere---------------</option>
<option value="sms.orange.nl">Dutchtone/Orange-NL</option>
<option value="ideacellular.net">IDEA Cellular (India)</option>
<option value="kapow.co.uk">Kapow!</option>
<option value="sms.mtel.net">M-Tel (Bulgaria)</option>
<option value="m1.com.sg">MobileOne</option>
<option value="sms.movistar.net.ar">Movistar</option>
<option value="sms.netcom.no">Netcom</option>
<option value="sms.eurotel.cz">O2 (Czech Republic)</option>
<option value="mmail.co.uk">O2 (United Kingdom)</option>
<option value="o2online.de">O2 (Germany)</option>
<option value="orange.net">Orange</option>
<option value="orange.fr">Orange (France)</option>
<option value="orange.pl">Orange (Poland)</option>
<option value="optusmobile.com.au">Optus</option>
<option value="mujoskar.cz">Oskar</option>
<option value="personal-net.com.ar">Personal (Argentina)</option>
<option value="smsonline.proximus.be">Proximus (Belgium)</option>
<option value="sfr.fr">SFR (France)</option>
<option value="mysmart.mymobile.ph">Smart Com (Philippines)</option>
<option value="starhub.net.sg">StarHub (Singapore)</option>
<option value="sms.t-mobile.at">T-Mobile (Austria)</option>
<option value="T-D1-SMS.de">T-Mobile (Germany)</option>
<option value="t-mobile.uk.net">T-Mobile (UK)</option>
<option value="gsm1800.telia.dk">Telia Denmark</option>
<option value="mobilpost.no">Telenor</option>
<option value="vxtras.com">Virgin Mobile UK</option>
<option value="vodafone.de">Vodafone Germany</option>
<option value="sms.vodafone.it">Vodafone Italy</option>
<option value="vodafone.pt">Vodafone Portugal</option>
<option value="vodafone.es">Vodafone Spain</option>
<option value="euromail.se">Vodafone Sweden</option>
<option value="vodafone.net">Vodafone UK</option>
<option value="c.vodafone.ne.jp">Vodafone Japan C</option>
<option value="h.vodafone.ne.jp">Vodafone Japan H</option>
<option value="t.vodafone.ne.jp">Vodafone Japan T</option>
</select>

*/
