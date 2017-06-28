<?php
namespace Khodja\Upload;

class Upload
{
    private static
    $alphabet = ['K','h','o','d','j','a', 'p', '_', 'B', 'f', 'S', 'c', 'O', '6', '-', '3', 'R', 'w', 'l', 'A', 'M',  'I', 'J', 'k', 'y', 'E', 'v', '4', '5', '2',  'C',  'H',  'T', 'b', 'W', '7', 'q', 'P', 'U', 's', 'm', 'V', 'Q', '0', 'G', 'n', 'F', 'x', 'e', 't',  'Y', '1', 'u', '8', 'L', 'i', 'z', 'D', 'Z', 'g', 'X', 'N', '9', 'r'],

    $fliped_alphabet = ['K' => 0, 'h' => 1, 'o' => 2, 'd' => 3, 'j' => 4, 'a' => 5, 'p' => 6, '_' => 7, 'B' => 8, 'f' => 9, 'S' => 10, 'c' => 11, 'O' => 12, 6 => 13, '-' => 14, 3 => 15, 'R' => 16, 'w' => 17, 'l' => 18, 'A' => 19, 'M' => 20, 'I' => 21, 'J' => 22, 'k' => 23, 'y' => 24, 'E' => 25, 'v' => 26, 4 => 27, 5 => 28, 2 => 29, 'C' => 30, 'H' => 31, 'T' => 32, 'b' => 33, 'W' => 34, 7 => 35, 'q' => 36, 'P' => 37, 'U' => 38, 's' => 39, 'm' => 40, 'V' => 41, 'Q' => 42, 0 => 43, 'G' => 44, 'n' => 45, 'F' => 46, 'x' => 47, 'e' => 48, 't' => 49, 'Y' => 50, 1 => 51, 'u' => 52, 8 => 53, 'L' => 54, 'i' => 55, 'z' => 56, 'D' => 57, 'Z' => 58, 'g' => 59, 'X' => 60, 'N' => 61, 9 => 62, 'r' => 63, ];

    private static $index = [];

    private static function getPathArr($catalog,$id)
    {
        $encoded=self::encode($id);
        $path=str_split(str_repeat(self::$alphabet[0], 6-strlen($encoded)).$encoded,2);
        array_unshift($path, 'uploads', $catalog);
        return $path;
    }
  
    public static function getPath($catalog,$id)
    {
        return '/'.implode('/', self::getPathArr($catalog,$id));
    }

    private static function encode($int)
    {
        $val = '';
        $len = 64;
        $mod = 0;

        while($int>0)
        {
            $mod=$int-floor($int/$len)*$len;
            $int=($int-$mod)/$len;
            $val=self::$alphabet[$mod].$val;
        }

        return $val;
    }

    private static function decode($str)
    {
        $val=0;
        foreach(array_reverse(str_split($str)) as $ind=>$char)
        {
            if(isset(self::$fliped_alphabet[$char]))
            {
                $val += self::$fliped_alphabet[$char]*pow(64, $ind);
            }
        }

        return $val;
    }
  
    public static function getFile($catalog, $id, $filter=null)
    {
        $files = self::getFiles($catalog,$id,$filter);
        if($files) {
            return $files[0];
        }

        return null;
    }

    public static function getFiles($catalog, $id, $filter=null)
    {
        if(isset(self::$index[$catalog.$id])) {
            return self::$index[$catalog.$id];
        }

        if(is_array($filter)) {
            $filter='*.{'.implode(',', $filter).'}';
        }
        else {
            $filter='*';
        }

        $files=[];
        $files=glob(public_path().self::getPath($catalog,$id).'/'.$filter,GLOB_BRACE);

        foreach($files as $ind=>$val) {
            $files[$ind] = str_replace(public_path(), '', $val);
        }

        self::$index[$catalog.$id]=$files;
        return $files;
    }

    public static function saveFile($catalog, $id, $file, $options = [])
    {
        //Перед сохранением одного файла удаляю предыдущие версии файлов
        self::removeFiles($catalog,$id);
        $path = public_path().self::getPath($catalog,$id).'/';
        
        if( !isset($options['name']) ) {
            $options['name'] = time();
        }

        $ex = $file->getClientOriginalExtension();
        $file->move($path, $options['name'].'.'.$ex);
        return $path.$options['name'].'.'.$ex;
    }

    public static function saveFiles($catalog, $id, $files, $options = [])
    {
        $path = public_path().self::getPath($catalog, $id).'/';

        if(!isset($options['name'])) {
            $options['name'] = time();
        }

        $n = count(self::getFiles($catalog,$id));

        foreach($files as $ind=>$file)
        {
            $ex = $file->getClientOriginalExtension();
            $i = $n + ($ind + 1);

            $file->move($path, $options['name'].('-'.$i).'.'.$ex);
        }
    }

    public static function swapFirst($catalog, $id, $i)
    {
        $files = self::getFiles($catalog, $id);
        $count = count($files);
        $index = $i - 1;

        if($count <= 1 || $i == 1 || !isset($files[$index])) {
            return false;
        }

        $file1 = public_path($files[0]);
        $file2 = public_path($files[$index]);
        $file2_tmp = public_path($files[$index]).'-tmp';

        rename($file1, $file2_tmp);
        rename($file2, $file1);
        rename($file2_tmp, $file2);

        return true;
    }

    public static function getImage($catalog,$id,$class='')
    {
        $file = self::getFile($catalog,$id);
        if($file) {
            return '<img src="'.$file.'" class="'.$class.'"/>';
        }

        return '<div class="no-image"></div>';
    }

    public static function getThumbImage($catalog, $id, $size,$class='')
    {
        $file = self::getFile($catalog,$id);
        $file = str_replace('uploads/'.$catalog.'/', '', $file);

        if($file) {
            // $ext = preg_replace('|^.*(\.\w+)$|is', '$1', $file);
            return '<img src="/uploads/thumb/'.$catalog.'/'.$size.$file.'" alt="" class="'.$class.'"/>';
        }
        return '';
    }

    public static function getThumbFiles($catalog, $id, $size)
    {
        $files = self::getFiles($catalog,$id);
        $new_files = [];
        foreach($files as $ind=>$file) {
            $file = str_replace('uploads/'.$catalog.'/', '', $file);
            $new_files[] = '/uploads/thumb/'.$catalog.'/'.$size.$file;
        }

        return $new_files;
    }

    public static function getThumb($catalog, $id, $size){
        $files = self::getFiles($catalog,$id);
        
        if($files) {
            return '/uploads/thumb/'.$catalog.'/'.$size.$files[0];
        }

        return null;
    }

    public static function getImages($catalog,$id,$class='')
    {
        $files = self::getFiles($catalog,$id);
        $res='';

        foreach($files as $ind=>$file) {
            $res.= '<img src="'.$file.'" class="'.$class.'"/>';
        }

        return $res;
    }

    public static function hasFiles($catalog,$id)
    {
        return count(self::getFiles($catalog,$id))>0;
    }

    public static function hasFile($catalog,$id)
    {
        return self::getFile($catalog,$id)!==null;
    }

    public static function removeFile($catalog,$id)
    {
        self::removeFiles($catalog, $id);
    }

    public static function removeFiles($catalog, $id, $file_names=null)
    {
        $remove = [];
        $files = self::getFiles($catalog,$id);

        if($file_names === null)
        {

            foreach ($files as $file) {
                unlink(public_path().$file);
            }
        }
        else
        {
            $path = self::getPath($catalog, $id);
            foreach($file_names as $ind=>$file) {
                
                $remove[] = $path.'/'.basename($file);
            }

            $n = 1;
            
            foreach($files as $key=>$file)
            {

                if(in_array($file, $remove))
                {
                    unlink(public_path().$file);
                }
                else
                {
                    // don't rename the file if exsits it already has with that name
                    if(($key + 1) != $n) 
                    {
                        $file = public_path($file);
                        rename($file, preg_replace('/-(\d+)(?!.*\d)/is', '-'.$n, $file));
                    }

                    $n++;
                }
            }
            
        }

    }

    public static function getImageSize($link,$width,$height)
    {
        $path=public_path().$link;
        list($real_width, $real_height) = getimagesize($path);
        $w_d=$width/$real_width;
        $h_d=$height/$real_height;
        $str='<img src="'.$link.'" ';

        if($w_d<$h_d)
        {
            $str.="height=\"$height\"";
        }
        else
        {
            $str.="width=\"$width\"";
        }

        return $str." />";
    }

}
