<?php

// Tag types;
const ANCHOR = 'a';
const DIV = 'div';
const H2 = 'h2';
const IMAGE = 'img';
const SOURCE = 'src';
const SPAN = 'span';

// Attributes
const CLS = 'class';
const LINK = 'href';

// Options
const SINGLE = 1;
const VALUE = 2;
const VALUES = 3;
const CONTENT = 4;


/**
 * erlaubt es ein DOM-Element nach Content zu durchsuchen.
 * @param DOM-Element $head das zu durchsuchende DOM-Element
 * @param String $type der Tag-Typ, nach welchem gesucht werden soll.
 * @param String $attr (optinales) Attribut, nach welchem gesucht werden soll. Ist es vorhanden, werden nur Tags berücksichtigt, die das Attribut haben.
 * @param String $val (optionaler) Wert des Attributs, nach welchem gesucht werden soll. Ist der Wert vorhanden, werden nur Tags berücksichtig, bei denen das mit $attr spezifizierte Attribut den Wert enthält
 * @param int $option (optionaler) Modifikator für die Ausgabe.
 		Wenn $option = SINGLE: nur das erste DOM-Element, dass den Filterkriterien entspricht wird zurückgegeben.
 		Wenn $option = VALUE: Attribut-Wert des ersten DOM-Elements, welches das Attribut $attr hat wird zurückgegeben
 		Wenn $option = VALUES: aus den Attribut-Werten aller DOM-Elemente, welches das Attribut $attr haben wird ein Array zusammengestellt und anschließend zurückgegeben
 		Wenn $option = CONTENT: Text des ersten DOM-Elements, das die Bedingungen erfüllt wird zurückgegeben
 		Default: alle DOM-Elemente, die die Bedingungen erfüllen werden zurückgegeben
 * @return String|Array|null
 */
function findElements($head,$type,$attr=null,$val=null,$option=0){
	$entities = $head->getElementsByTagName($type);
	$elements = [];
	foreach ($entities as $entity){
		if (!empty($attr)){
			if (!$entity->hasAttribute($attr)) continue;
			if (!empty($val)){
				$values = explode(' ',trim($entity->getAttribute($attr)));
				if (!in_array($val, $values)) continue;
			}
		}
		switch ($option){
			case CONTENT: return trim($entity->nodeValue);
			case SINGLE: return $entity;
			case VALUE: return trim($entity->getAttribute($attr));
			case VALUES:
				$elements[] = trim($entity->getAttribute($attr));
				break;
			default:
				$elements[] = $entity;
		}
	}
	if (empty($elements)) return null;
	return $elements;
}

function parseParam($url,$key = null){
	$parts = explode('?', $url,2);
	if (count($parts)<2) return null;
	$parts = explode('&', $parts[1]);
	$param = [];
	foreach ($parts as $part){
		$entry = explode('=', $part,2);
		if ($entry[0]==$key) return $entry[1];
		$param[$entry[0]] = $entry[1];
	}
	return $param;
}