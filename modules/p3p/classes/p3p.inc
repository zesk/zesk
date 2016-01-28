<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/p3p/classes/p3p.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:14 PM
 */

/*
compact-access =
	"NOI" | ; for <nonident/>
	"ALL" | ; for <all/>
	"CAO" | ; for <contact-and-other/>
	"IDC" | ; for <ident-contact/>
	"OTI" | ; for <other-ident/>
	"NON"   ; for <none/>

compact-disputes =
	"DSP" ; there are some DISPUTES

compact-recipient =
	"OUR"        | ; for <ours/>
	"DEL" [creq] | ; for <delivery/>
	"SAM" [creq] | ; for <same/>
	"UNR" [creq] | ; for <unrelated/>
	"PUB" [creq] | ; for <public/>
	"OTR" [creq]   ; for <other-recipient/>

compact-purpose =
	CUR"        | ; for <current/>
	"ADM" [creq] | ; for <admin/>
	"DEV" [creq] | ; for <develop/>
	"TAI" [creq] | ; for <tailoring/>
	"PSA" [creq] | ; for <pseudo-analysis/>
	"PSD" [creq] | ; for <pseudo-decision/>
	"IVA" [creq] | ; for <individual-analysis/>
	"IVD" [creq] | ; for <individual-decision/>
	"CON" [creq] | ; for <contact/>
	"HIS" [creq] | ; for <historical/>
	"TEL" [creq] | ; for <telemarketing/>
	"OTP" [creq]   ; for <other-purpose/>

creq =
	"a"| ;"always"
	"i"| ;"opt-in"
	"o"  ;"opt-out"

compact-retention =
	"NOR" | ; for <no-retention/>
	"STP" | ; for <stated-purpose/>
	"LEG" | ; for <legal-requirement/>
	"BUS" | ; for <business-practices/>
	"IND"   ; for <indefinitely/>

compact-remedies =
	"COR" | ; for <correct/>
	"MON" | ; for <money/>
	"LAW"   ; for <law/>

compact-non-identifiable =
	"NID" ; for <NON-IDENTIFIABLE/>

compact-categories =
	"PHY" | ; for <physical/>
	"ONL" | ; for <online/>
	"UNI" | ; for <uniqueid/>
	"PUR" | ; for <purchase/>
	"FIN" | ; for <financial/>
	"COM" | ; for <computer/>
	"NAV" | ; for <navigation/>
	"INT" | ; for <interactive/>
	"DEM" | ; for <demographic/>
	"CNT" | ; for <content/>
	"STA" | ; for <state/>
	"POL" | ; for <political/>
	"HEA" | ; for <health/>
	"PRE" | ; for <preference/>
	"LOC" | ; for <location/>
	"GOV" | ; for <government/>
	"OTC"   ; for <other-category/>

*/
