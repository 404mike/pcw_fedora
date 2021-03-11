<?php

class GetImages {


  public function __construct()
  {
    $this->loopFiles();
  }

  private function loopFiles()
  {
    $files = glob('data/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $json = file_get_contents($file);
      $data = json_decode($json,true);
      $this->downloadImage($data['files']);
    }
  }

  private function downloadImage($files)
  {
    foreach($files as $k => $v) {
      // print_r($v);
      // die();


      $url = str_replace('http://drupalvm.local/','https://www.peoplescollection.wales/',$v['url']);
      $saveto = "images/$v[originalFilename]";

      echo "Downloading $saveto\n";
      if(file_exists("images/$v[originalFilename]")) return;

      $ch = curl_init ($url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
      $raw=curl_exec($ch);
      curl_close ($ch);
      if(file_exists($saveto)){
          unlink($saveto);
      }
      $fp = fopen($saveto,'x');
      fwrite($fp, $raw);
      fclose($fp);

    }
  }
}

(new GetImages());