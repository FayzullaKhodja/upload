<?php

Route::get(Config::get('upload.thumb_dir').'/{catalog}/{size}/{tt}/{ta}/{tm}/{file}',
	function ($catalog, $size_str, $tt, $ta, $tm, $file) {

    $size = Config::get('upload.image_sizes.' . $catalog .'.'. $size_str);

    if ($size === null)
    {
    	abort(404);
    }

    $original_path = public_path(Config::get('upload.main_dir')."/$catalog/$tt/$ta/$tm/$file");
    $new_path = public_path(Request::path());
    $new_dir = dirname($new_path);

    $img = Image::make($original_path)->fit($size[0], $size[1]);

    mkdir($new_dir, 0777, true);
    $img->save($new_path);

    return $img->response();
});