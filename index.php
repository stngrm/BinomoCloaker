<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

require 'bnc.php';
include 'settings.php';
include 'htmlprocessing.php';
include 'logging.php';

//передаём все параметры в кло
$cloacker = new Cloacker();
$cloacker->os_white = $os_white; 
$cloacker->country_white = $country_white;
$cloacker->ip_black = $ip_black; 
$cloacker->tokens_black = $tokens_black;
$cloacker->ua_black = $ua_black;
$cloacker->block_without_referer = $block_without_referer;

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($full_cloak_on) {
	write_visitors_to_log($cloacker->detect,['full_cloak'],1,'','');
	white();
	return;
}

//Проверяем зашедшего пользователя
$check_result = $cloacker->check();
if (!isset($cloacker->result))
	$cloacker->result=['OK'];

if ($check_result == 0) //Обычный юзверь
{
	black();
} 
else //Обнаружили бота или модера
{
	write_visitors_to_log($cloacker->detect,$cloacker->result,$check_result,'','');
	white();
	return;
}

function white(){
	global $white_action,$white_folder_name,$white_redirect_url,$white_redirect_type,$white_curl_url,$white_error_code;
	switch($white_action){
		case 'error':
  	        http_response_code($white_error_code);
    		break;
		case 'site':
			echo load_content($white_folder_name,-1);
			break;
		case 'curl':
			echo load_white_curl($white_curl_url);
			break;
		case 'redirect':
			if ($white_redirect_type==302){
				header('Location: '.$white_redirect_url);
				exit;
			}
			else{
				header('Location: '.$white_redirect_url, true, $white_redirect_type);
				exit;
			}
			break;
	}
	return;
}

function black(){
	global $cloacker,$check_result,$black_action,$black_redirect_type, $black_redirect_url,$black_preland_folder_name,$black_land_folder_name;
	switch($black_action){
		case 'site':
			//если мы используем прокладки
			if ($black_preland_folder_name!='')
			{
				//A-B тестирование прокладок
				$prelandings = explode(",", $black_preland_folder_name);
				$r = rand(0, count($prelandings) - 1);

				//A-B тестирование лендингов
				$landings = explode(",", $black_land_folder_name);
				$t = rand(0, count($landings) - 1);
				
				write_visitors_to_log($cloacker->detect,$cloacker->result,$check_result,$prelandings[$r],$landings[$t]);
				echo load_content($prelandings[$r],$t);
			}
			else //если у нас только ленды без прокл
			{ 
				//A-B тестирование лендингов
				$landings = explode(",", $black_land_folder_name);
				$r = rand(0, count($landings) - 1);
				write_visitors_to_log($cloacker->detect,$cloacker->result,$check_result,'',$landings[$r]);
				echo load_content($landings[$r],-1);
			}	
			break;
		case 'redirect':
			if ($black_redirect_type==302){
				header('Location: '.$black_redirect_url);
				exit;
			}
			else{
				header('Location: '.$black_redirect_url, true, $black_redirect_type);
				exit;
			}
			break;
	}
	return;
}
?>