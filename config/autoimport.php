<?php

set_time_limit(0);

$ical_import_urls=array(
		'http://grical.org/s/?query=e&view=ical',
		'https://calcifer.datenknoten.me/all.ics',
		array('url' => 'https://www.google.com/calendar/ical/sfgnd1tl8n1fnkl0v3ne1oq9jc%40group.calendar.google.com/public/basic.ics','tag'=>array('FreiRaum','Jena')),
		array('url' => 'http://www.f-haus.de/cms/?plugin=all-in-one-event-calendar&controller=ai1ec_exporter_controller&action=export_events&no_html=true','tag' => array('FHaus','Jena'))
);

$location = null;
if (isset($_GET['autoimport'])) $location=trim($_GET['autoimport']);
switch ($location){
	case 1:
		if (isset($ical_import_urls)){
			foreach ($ical_import_urls as $item){
				if (is_array($item)){
					importIcal($item['url'],$item['tag']);
				} else importIcal($item);
			}
		}
		break;
	case 2:
		Psychochor::read_events();
		break;
	case 3:
		EBurg::read_events();
		break;
	case 4:
		KasseTurm::read_events();
		break;
	case 5:
		SevenGera::read_events();
		break;
	case 6:		
		Wotufa::read_events();
		break;
	case 7:
		FromHell::read_events();
		break;
	case 8:
		Rosenkeller::read_events();
		break;
	case 9:
		WagnerVerein::read_events();
		break;
	case 10:
		CosmicDawn::read_events();
		break;
	case 11:
		Kassablanca::read_events();
		break;
	case 12:
		SaechsischerBahnhof::read_events();
		break;
	default:
		print "no location number given!";
}