<?php
/**
 * フォルダ内のファイルを合成
 */

$opt = getopt('d:r:');

if (!empty($opt['d'])) {
  $dirpath = $opt['d'];
}
if (!empty($opt['r'])) {
  $isRightLeft = $opt['r'] == 'y' ? true : false;
}

if (empty($dirpath)) {
  echo "フォルダパス", PHP_EOL;
  $dirpath = trim(fgets(STDIN)) ?: __DIR__;
}

if (!is_dir($dirpath)) {
  echo $dirpath, ' is not dir', PHP_EOL;
  exit;
}

if (!isset($isRightLeft)) {
  echo '最初が右ならy', PHP_EOL;
  $isRightLeft = trim(fgets(STDIN)) == 'y' ? true : false;
}
echo $dirpath, ',', ($isRightLeft ? 'right-left' : 'left-right'), PHP_EOL;

$files = scandir($dirpath);
if ($files === false) {
  echo 'scandir error', PHP_EOL;
  exit;
}

$files = array_filter($files, function($v) use ($dirpath) {
  if (strpos($v, '.') === 0) {
    return false;
  }
  $filePath = $dirpath . DIRECTORY_SEPARATOR . $v;
  if (is_dir($filePath)) {
    return false;
  }
  $mime = mime_content_type($filePath);
  if (strpos($mime, 'image/') !== 0) {
    return false;
  }
  if (preg_match('/\d+\D*?-\d+\.png$/', $v)) {
    return false;
  }

  return true;
});

$files = array_values($files);

array_walk($files, function(&$v) use($dirpath) { $v = $dirpath . DIRECTORY_SEPARATOR. $v; });

for ($i=$isRightLeft ? 0 : 1; $i < count($files) - 1; $i += 2) {
  $rightFile = $files[$i];

  $pathInfo = pathinfo($rightFile);
  if(!preg_match('/(\d+)\D*?\.[^.]+$/', $pathInfo['basename'], $mt)){
    // 数字の無いファイルは飛ばす
    echo $rightFile, ' not match', PHP_EOL;
    $i--;
    continue;
  }
  $nextFileNumber = sprintf('%0*d', strlen($mt[1]), intval($mt[1]) + 1);

  $newFileName = "{$pathInfo['filename']}-{$nextFileNumber}.png";

  // 作成済みならパス
  if (file_exists($dirpath.DIRECTORY_SEPARATOR.$newFileName)) {
    continue;
  }
  $leftFile = $files[$i+1];

  $imageSize = getimagesize($rightFile);
  $width = $imageSize[0];
  $height = $imageSize[1];
  $newFile = imagecreatetruecolor($width * 2, $height);
  $rightFileRs = imagecreatefromstring(file_get_contents($rightFile));
  $leftFileRs = imagecreatefromstring(file_get_contents($leftFile));
  imagecopy($newFile, $rightFileRs, $width, 0, 0, 0, $width, $height);
  imagecopy($newFile, $leftFileRs, 0, 0, 0, 0, $width, $height);
  imagepng($newFile, $dirpath.DIRECTORY_SEPARATOR.$newFileName);
  imagedestroy($newFile);
  imagedestroy($rightFileRs);
  imagedestroy($leftFileRs);
  error_log($dirpath . DIRECTORY_SEPARATOR . $newFileName);
}
