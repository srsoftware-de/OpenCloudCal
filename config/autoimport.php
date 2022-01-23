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
		Gewerkschaftshaus::read_events();
		break;
	case 4:
		importIcal('https://www.f-haus.de/cms/veranstaltungen/liste/?ical=1',array('FHaus','Jena'));
		break;
	case 5:
		ZehnTausendVolt::read_events();
		break;
	case 6:
		Psychochor::read_events();
		break;
	case 7:
		Bandhaus::read_events();
		break;
	case 8:
		KasseTurm::read_events();
		break;
	case 9:
		FestungKoenigstein::read_events();
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
		//WagnerVerein::read_events();
		importIcal('https://cafewagner.de/de/programm/all.ics',['CafeWagner','Jena']);
		break;
	case 14:
		CosmicDawn::read_events();
		break;
	case 15:
		Kassablanca::read_events();
		break;
	case 16:
		JGStadtMitte::read_events();
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
		JenaKultur::read_events();
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
		EBurg::read_events();
		break;
	case 27:
		Taeubchenthal::read_events();
		break;
	case 28:
		HellRaiser::read_events();
		break;

	case 29:
		Werk2::read_events();
		break;

	case 30:
		importIcal('http://dornburg-camburg.de/?plugin=all-in-one-event-calendar&controller=ai1ec_exporter_controller&action=export_events&no_html=true',['Camburg']);
		break;

	default:
		http_response_code(404);
		die('no valid location number given!');
}
