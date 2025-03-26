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
    $count = 0;
    foreach($files as $k => $file) {
      $json = file_get_contents($file);
      $data = json_decode($json, true);
      $f = count($data['files']);
      // print_r($data['files']);
      echo "F = $f\n";
      $count += $f;
      // $this->downloadImage($data['files']);
    }

    echo "Total images: $count\n";
  }


  private function downloadImage(array $files): void
  {
    $count = 0;
      foreach ($files as $k => $v) {
          $url = str_replace('http://drupalvm.local/', 'https://www.peoplescollection.wales/', $v['url']);

      }
  }
}

(new GetImages());