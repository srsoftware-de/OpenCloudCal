<?php

const ANCHOR = 'a';
const CLS = 'class';
const DIV = 'div';
const LINK = 'href';
const H2 = 'h2';
const IMAGE = 'img';
const SOURCE = 'src';

const SINGLE = 1;
const VALUE = 2;
const VALUES = 3;
const CONTENT = 4;


function findElements($head,$type,$attr=null,$val=null,$option=0){
	$entities = $head->getElementsByTagName($type);
	$elements = [];
	foreach ($entities as $entity){
		if (!empty($attr)){
			if (!$entity->hasAttribute($attr)) continue;
			if (!empty($val)){
				$values = explode(' ',$entity->getAttribute($attr));
				if (!in_array($val, $values)) continue;
			}
		}
		switch ($option){
			case CONTENT: return $entity->nodeValue;
			case SINGLE: return $entity;
			case VALUE: return $entity->getAttribute($attr);
			case VALUES:
				$elements[] = $entity->getAttribute($attr);
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