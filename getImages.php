<?php

class GetImages {


  public function __construct()
  {
    // make sure the images directory exists
    if(!is_dir('images')) {
      mkdir('images');
    }

    $this->loopFiles();
  }

  private function loopFiles()
  {
    $loop = 0;
    $files = glob('data/*.{json}', GLOB_BRACE);
  
    foreach($files as $k => $file) {
      $json = file_get_contents($file);
      $data = json_decode($json, true);
      
      $response = $this->downloadImage($data['files']);
  
      if ($response === "File already exists") {
        continue;
      }
  
      $loop++;
  
      if ($loop >= 30) {
        echo "Sleeping for 5 seconds\n";
        sleep(5);
        $loop = 0;
      }  
    }
  }
  

  private function downloadImage($files)
  {
    foreach($files as $k => $v) {
      $url = str_replace('http://drupalvm.local/','https://www.peoplescollection.wales/',$v['url']);
      $saveto = "images/$v[originalFilename]";

      echo "Downloading $saveto\n";
      if(file_exists("images/$v[originalFilename]")) return 'File already exists';

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

      return 'File downloaded';
    }
  }
}

(new GetImages());