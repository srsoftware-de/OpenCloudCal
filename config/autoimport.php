<?php
$ical_import_urls=array(
		'http://grical.org/s/?query=e&view=ical',
		'https://calcifer.datenknoten.me/all.ics',
		array('url' => 'https://www.google.com/calendar/ical/sfgnd1tl8n1fnkl0v3ne1oq9jc%40group.calendar.google.com/public/basic.ics','tag'=>array('FreiRaum','Jena')),
		array('url' => 'http://www.f-haus.de/cms/?plugin=all-in-one-event-calendar&controller=ai1ec_exporter_controller&action=export_events&no_html=true','tag' => array('FHaus','Jena'))
);
$parse_imports=array(
		array('url'=>'https://rosenkeller.org/index.html','tags' => array('Rosenkeller','Jena'),'coords'=>'50.929463, 11.584644','location'=>'Rosenkeller Jena'),
		array('url'=>'http://www.wagnerverein-jena.de/','tags'=>array('CafeWagner','Jena'),'coords'=>'50.931251, 11.580310','location'=>'Café Wagner, Wagnergasse 26, Jena'),
		array('url'=>'http://www.cosmic-dawn.de/','tags'=>array('CosmicDawn','Jena'),'coords'=>'50.936508, 11.592745','location'=>'Kulturbahnhof, Spitzweidenweg 28, Jena')
);
