<?php
use OSS\OssClient;
use OSS\Core\OssException;

spl_autoload_register('classLoader');

date_default_timezone_set('PRC');

if(!isset($_GET['dir'])){
	$dir='';
}else{
	$dir=$_GET['dir'];

}
$params = explode('/', $dir);
$i=0;
$newparams=array();
foreach($params as $key => $dirname){
	if(!empty($dirname)){
		$newparams[$i]=$dirname;
		$i=$i+1;
	}
}
unset($newparams[count($newparams)-1]);
$lastdir='';
if(count($newparams) > 0){
	foreach($newparams as $dirname){
		$lastdir .= $dirname.'/';
	}
}
$ossClient=getOssClient();
//需修改
$bucket="bucketname";
$list=listObjects($ossClient, $bucket, $dir);
// p($list);

$showList=array();
$showList['head'][0]=array(
	'name'=>'根目录',
	'url'=>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?dir='
);
$showList['home'][0]=array(
	'name'=>'上层文件夹',
	'url'=>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?dir='.$lastdir
);

if(array_key_exists('dir',$list)){
	$i=0;
	$dirList = $list['dir'];
	foreach(array_reverse($dirList) as $ossObject){
		$dirArr = explode("/",$ossObject);
		$showList['dir'][$i]=array(
			'name'=>$dirArr[count($dirArr)-2],
			'url'=>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?dir='.$ossObject
		);
		$i+=1;
	}
}
if(array_key_exists('file',$list)){
	$fileList=$list['file'];
	// p($fileList);
	$i=0;
	foreach(array_reverse($fileList) as $ossObject){
		$fileArr=explode("/", $ossObject);
		$showList['file'][$i]=array(
			'name'=>$fileArr[count($fileArr)-1],
			//需修改
			'url'=>'地址前缀'.$ossObject
		);
		$i+=1;
	}

}
include './download.html';

function echo_link($showList,$type){
	if(array_key_exists($type,$showList)){
		if($type == 'dir'){
			$image ='folder.png';
		}elseif($type == 'file'){
			$image ='default.png';
		}elseif($type == 'head'){
			$image = 'folder-home.png';
		}elseif($type == 'home'){
			$image = 'back.gif';
		}

		foreach($showList[$type] as $list){
			if($type == 'file'){
				if(strrchr($list['url'],'/') == '/'){
					continue;
				}else{
					echo '<li><img src="images/'.$image.'"><a href="'.$list['url'].'"> '.$list['name'].'</a></li>';
				}
			}elseif($type == 'dir'){
				if(strrchr($list['url'],'//') == '//'){
					continue;
				}else{
					echo '<li><img src="images/'.$image.'"><a href="'.$list['url'].'"> '.$list['name'].'</a></li>';
				}
			}else{
				echo '<li><img src="images/'.$image.'"><a href="'.$list['url'].'"> '.$list['name'].'</a></li>';
			}
		}
	}
}

function classLoader($class)
{
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
	$file = __DIR__ . DIRECTORY_SEPARATOR . $path . '.php';
	if (file_exists($file)) {
		require_once $file;
	}
}



function getOssClient(){
	//需修改 账号密码
	$accessKeyId = '？？';
	$accessKeySecret = '？？？';
	$endpoint = '？？';

	try {
		$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false);
	} catch (OssException $e) {
		printf($e->getMessage() . "\n");
		return null;
	}
	return $ossClient;
}
function deleteObject($ossClient, $bucket, $object)
{
    //$object = "oss-php-sdk-test/upload-test-object-name.txt";
    try {
        $ossClient->deleteObject($bucket, $object);
    } catch (OssException $e) {
        //printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    //print(__FUNCTION__ . ": OK" . "\n");
}
function listObjects($ossClient, $bucket, $dir)
{
	$prefix = $dir;
	$delimiter = '/';
	$nextMarker = '';
	$maxkeys = 1000;
	$options = array(
			'delimiter' => $delimiter,
			'prefix' => $prefix,
			'max-keys' => $maxkeys,
			'marker' => $nextMarker,
			);
	try {
		$listObjectInfo = $ossClient->listObjects($bucket, $options);
	} catch (OssException $e) {
		return;
	}
	$objectList = $listObjectInfo->getObjectList(); // 文件列表
	$prefixList = $listObjectInfo->getPrefixList(); // 目录列表
	if (!empty($objectList)) {
		foreach ($objectList as $objectInfo) {
			$filelist[]=$objectInfo->getKey();
		}
	}
	if (!empty($prefixList)) {
		foreach ($prefixList as $prefixInfo) {
			$dirlist[]=$prefixInfo->getPrefix();
		}
	}


	$returninfo=array();

	if(!empty($filelist)){
		$returninfo["file"]=$filelist;
	}
	if(!empty($dirlist)){
		$returninfo["dir"]=$dirlist;
	}
	return $returninfo;

}

function p($var){
	if(is_bool($var)){
		var_dump($var);


	}elseif (is_null($var)) {
		var_dump(NULL);


	}else{
		echo '<pre style="position:relative;z-index:1000;padding:10px;border-radius:5px;background:#f5f5f5;border:1px solid #aaa;font-size:14px;line-height:18px;opacity:0.9;">'.print_r($var,true).'</pre>';


	}

}
?>
