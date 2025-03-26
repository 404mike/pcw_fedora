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
    $files = glob('data/*.{json}', GLOB_BRACE);
  
    foreach($files as $k => $file) {
      $json = file_get_contents($file);
      $data = json_decode($json, true);
      
      $this->downloadImage($data['files']);
    }
  }


  private function downloadImage(array $files): void
  {
      foreach ($files as $k => $v) {
          $url = str_replace('http://drupalvm.local/', 'https://www.peoplescollection.wales/', $v['url']);
          $saveto = "images/$v[originalFilename]";
  
          if (file_exists($saveto)) {
              // echo "File already exists: $saveto\n";
              continue; // Skip to the next file
          }
  
          echo "Downloading $saveto\n";
  
          $ch = curl_init($url);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
          $raw = curl_exec($ch);
          curl_close($ch);
  
          if ($raw === false) {
              echo "Failed to download: $url\n";
              die();
          }
  
          if (file_exists($saveto)) {
              unlink($saveto); // Remove any existing file with the same name
          }
  
          $fp = fopen($saveto, 'x');
          if ($fp === false) {
              echo "Failed to save file: $saveto\n";
              continue; // Skip to the next file
          }
  
          fwrite($fp, $raw);
          fclose($fp);
  
          echo "File downloaded: $saveto\n";
          sleep(2);
      }
  }
}

(new GetImages());