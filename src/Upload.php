<?php
namespace Khodja\Upload;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class Upload
{
    /**
     * Alphabet for encrypting
     * 
     * @var array
     */
    private static $alphabet;

    /**
     * Fliped Alphabet for decrypting
     * 
     * @var array
     */
    private static $fliped_alphabet;

    /**
     * Indexes
     * 
     * @var array
     */
    private static $index = [];
    
    public function __construct()
    {
        self::$alphabet = Config::get('upload.alphabet');
        self::$fliped_alphabet = Cache::get('upload_flipped_alphabet', function () {
            return array_flip(self::$alphabet);
        });
    }

    /**
     * Get an array of paths
     * @param  string $catalog
     * @param  int    $id
     * 
     * @return string         
     */
    private static function getPathArr($catalog, $id)
    {
        $encoded = self::encode($id);
        $path = str_split(str_repeat(self::$alphabet[0], 6-strlen($encoded)).$encoded,2);
        array_unshift($path, Config::get('upload.main_dir'), $catalog);

        return $path;
    }

    /**
     * Get the path
     * 
     * @param  string $catalog
     * @param  int    $id    
     *  
     * @return string         
     */
    public static function getPath($catalog, $id)
    {
        return '/'.implode('/', self::getPathArr($catalog,$id));
    }

     /**
     * Encoding identifier
     * 
     * @param  string $str
     * 
     * @return string
     */
    private static function encode($int)
    {
        $val = '';
        $len = 64;
        $mod = 0;

        while ($int>0)
        {
            $mod = $int-floor($int/$len)*$len;
            $int = ($int-$mod)/$len;
            $val = self::$alphabet[$mod].$val;
        }

        return $val;
    }

    /**
     * Decoding identifier
     * 
     * @param  string $str
     * 
     * @return string
     */
    private static function decode($str)
    {
        $val = 0;
        foreach (array_reverse(str_split($str)) as $ind=>$char)
        {
            if (isset(self::$fliped_alphabet[$char]))
            {
                $val += self::$fliped_alphabet[$char]*pow(64, $ind);
            }
        }

        return $val;
    }
    
    /**
     * Get the path to the file
     * 
     * @param  string $catalog [description]
     * @param  int    $id      [description]
     * 
     * @return string|null
     */
    public static function getFile($catalog, $id)
    {
        $files = self::getFiles($catalog, $id);
        if ($files)
        {
            return $files[0];
        }

        return null;
    }

    /**
     * Get the path to the files 
     * 
     * @param  string $catalog
     * @param  int    $id     
     * @param  array  $filter 
     * 
     * @return array 
     */
    public static function getFiles($catalog, $id, $filter=null)
    {
        if (isset(self::$index[$catalog.$id]))
        {
            return self::$index[$catalog.$id];
        }

        if (is_array($filter))
        {
            $filter='*.{'.implode(',', $filter).'}';
        }
        else
        {
            $filter='*';
        }

        $files = [];
        $files = glob(public_path().self::getPath($catalog,$id).'/'.$filter, GLOB_BRACE);

        foreach ($files as $ind=>$val)
        {
            $files[$ind] = str_replace(public_path(), '', $val);
        }

        self::$index[$catalog.$id] = $files;

        return $files;
    }

    /**
     * Save file
     * 
     * @param  string $catalog
     * @param  int    $id 
     * @param  file   $file
     * @param  array  $options
     * 
     * @return string
     */
    public static function saveFile($catalog, $id, $file, $options = [])
    {
        // Before saving a file, delete the previous versions of the files
        self::removeFiles($catalog,$id);
        $path = public_path().self::getPath($catalog,$id).'/';
        
        if (!isset($options['name']))
        {
            $options['name'] = time();
        }

        $ex = $file->getClientOriginalExtension();
        $file->move($path, $options['name'].'.'.$ex);

        return $path.$options['name'].'.'.$ex;
    }

    /**
     * Save files
     * 
     * @param  string $catalog
     * @param  int    $id 
     * @param  array  $files
     * @param  array  $options
     * 
     * @return null
     */
    public static function saveFiles($catalog, $id, $files, $options = [])
    {
        $path = public_path().self::getPath($catalog, $id).'/';

        if (!isset($options['name']))
        {
            $options['name'] = time();
        }

        $n = count(self::getFiles($catalog,$id));

        foreach ($files as $ind=>$file)
        {
            $ex = $file->getClientOriginalExtension();
            $i = $n + ($ind + 1);

            $file->move($path, $options['name'].('-'.$i).'.'.$ex);
        }
    }

    /**
     * Swap first file with given file
     * 
     * @param  string $catalog
     * @param  int    $id     
     * @param  int    $i       index of file in the directory
     * 
     * @return boolean
     */
    public static function swapFirst($catalog, $id, $i)
    {
        $files = self::getFiles($catalog, $id);
        $count = count($files);
        $index = $i - 1;

        if ($count <= 1 || $i == 1 || !isset($files[$index]))
        {
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

    /**
     * Get image tag with source
     * 
     * @param  string $catalog
     * @param  int    $id     
     * @param  string $class  
     * 
     * @return string
     */
    public static function getImage($catalog, $id, $class='')
    {
        $file = self::getFile($catalog, $id);
        if ($file)
        {
            return '<img src="'.$file.'" class="'.$class.'"/>';
        }

        return '<div class="no-image"></div>';
    }

    /**
     * Get thumb image tag
     * 
     * @param  string $catalog
     * @param  int    $id     
     * @param  string $size   
     * @param  string $class  
     * 
     * @return string
     */
    public static function getThumbImage($catalog, $id, $size, $class='')
    {
        $file = self::getFile($catalog,$id);
        $file = str_replace(Config::get('upload.main_dir').'/'.$catalog.'/', '', $file);

        if ($file)
        {
            return '<img src="'.Config::get('upload.thumb_dir').'/'.$catalog.'/'.$size.$file.'" alt="" class="'.$class.'"/>';
        }

        return '';
    }

    /**
     * Get all thumb files path
     * 
     * @param  string $catalog
     * @param  int    $id
     * @param  string $size
     * 
     * @return array         
     */
    public static function getThumbFiles($catalog, $id, $size)
    {
        $files = self::getFiles($catalog,$id);
        $new_files = [];

        foreach ($files as $ind=>$file) 
        {
            $file = str_replace(Config::get('upload.main_dir').'/'.$catalog.'/', '', $file);
            $new_files[] = Config::get('upload.thumb_dir').'/'.$catalog.'/'.$size.$file;
        }

        return $new_files;
    }

    /**
     * Generate path for a thumb image
     * 
     * @param  string $catalog
     * @param  int    $id
     * @param  string $size 
     * 
     * @return boolean|string
     */
    public static function getThumbFile($catalog, $id, $size){
        $files = self::getFiles($catalog,$id);
        
        if ($files)
        {
            $file = str_replace(Config::get('upload.main_dir').'/'.$catalog.'/', '', $files[0]);
            return Config::get('upload.thumb_dir').'/'.$catalog.'/'.$size.$file;
        }

        return null;
    }

    /**
     * Whether the files contains a catalog folder
     * 
     * @param  string $catalog
     * @param  int    $id 
     * 
     * @return boolean
     */
    public static function hasFiles($catalog, $id)
    {
        return count(self::getFiles($catalog,$id)) > 0;
    }

    /**
     * Whether the file contains a catalog folder
     * 
     * @param  string $catalog
     * @param  int    $id 
     * 
     * @return boolean
     */
    public static function hasFile($catalog, $id)
    {
        return self::getFile($catalog,$id) !== null;
    }

    /**
     * Remove file
     * @param  string $catalog
     * @param  int    $id     
     * 
     * @return string
     */
    public static function removeFile($catalog, $id)
    {
        self::removeFiles($catalog, $id);
    }

    /**
     * Remove files
     * 
     * @param  string $catalog
     * @param  int    $id
     * @param  array  $file_names
     * 
     * @return boolean
     */
    public static function removeFiles($catalog, $id, $file_names = null)
    {
        $removed = [];
        $files = self::getFiles($catalog,$id);

        if ($file_names === null)
        {
            foreach ($files as $file) {
                unlink(public_path($file));
            }
        }
        else
        {
            $path = self::getPath($catalog, $id);
            foreach ($file_names as $ind=>$file)
            {
                $removed[] = $path.'/'.basename($file);
            }
            $n = 1;
            
            foreach ($files as $key=>$file)
            {
                if(in_array($file, $removed))
                {
                    unlink(public_path($file));
                }
                else
                {
                    // Don't rename the file if it exists with this name
                    if(($key + 1) != $n) 
                    {
                        $file = public_path($file);
                        rename($file, preg_replace('/-(\d+)(?!.*\d)/is', '-'.$n, $file));
                    }

                    $n++;
                }
            }
        }

        // Remove all thumb images
        foreach (Config::get('upload.image_sizes.'.$catalog, []) as $dir => $size)
        {
            foreach (self::getThumbFiles($catalog, $id, $dir) as $file)
            {
                $file = public_path($file);
                if (file_exists($file))
                {
                    unlink($file);
                }
            }
        }

        return true;
    }

}
