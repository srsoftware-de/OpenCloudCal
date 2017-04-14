<?php

set_time_limit(0);

$location = null;
if (isset($_GET['autoimport'])) $location=trim($_GET['autoimport']);
switch ($location){
	case 1:
		importIcal('http://grical.org/s/?query=e&view=ical');
		break;
	case 2:
		importIcal('https://calcifer.datenknoten.me/all.ics');
		break;
	case 3:
		importIcal('https://www.google.com/calendar/ical/sfgnd1tl8n1fnkl0v3ne1oq9jc%40group.calendar.google.com/public/basic.ics',array('FreiRaum','Jena'));
		break;
	case 4:
		importIcal('http://www.f-haus.de/cms/?plugin=all-in-one-event-calendar&controller=ai1ec_exporter_controller&action=export_events&no_html=true',array('FHaus','Jena'));
		break;
	case 5:
		importIcal('https://tockify.com/api/feeds/ics/jg.stadtmitte',array('JG.Stadtmitte','Jena'));
		break;
	case 6:
		Psychochor::read_events();
		break;
	case 7:
		EBurg::read_events();
		break;
	case 8:
		KasseTurm::read_events();
		break;
	case 9:
		AtEvents::read_events();
		break;
	case 10:	
		Wotufa::read_events();
		break;
	case 11:
		FromHell::read_events();
		break;
	case 12:
		Rosenkeller::read_events();
		break;
	case 13:
		WagnerVerein::read_events();
		break;
	case 14:
		CosmicDawn::read_events();
		break;
	case 15:
		Kassablanca::read_events();
		break;
	case 16:
		SaechsischerBahnhof::read_events();
		break;
	case 17:
		SaaleGaerten::read_events();
		break;
	case 18:
		BiClub::read_events();
		break;
	case 19:
		Moritzbastei::read_events();
		break;
	case 20:
		MpireJena::read_events();
		break;
	case 21:
		VolkshausJena::read_events();
		break;
	case 22:
		CKeller::read_events();
		break;
	case 23:
		FourRooms::read_events();
		break;
	case 24:
		TheLondoner::read_events();
		break;
	case 25:
		importIcal('http://comma-club-gera.de/veranstaltungen-gera/?ical=1',array('Comma','Gera'));
		break;
	case 26:
		MedClub::read_events();
		break;
		
	default:
		print "no location number given!";
}
