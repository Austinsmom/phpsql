<?php 
/**
 * Copyright (C) 2013  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.    
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 * @package Addiks
 */

namespace Addiks\Database\Resource\Filesystem;

use Addiks\Database\Resource\Filesystem;

use Addiks\Common\Value\Text\Filepath;

class Real extends Filesystem{
	
	public function getFileContents(Filepath $filePath){
		return file_get_contents($filePath);
	}
	
	public function putFileContents(Filepath $filePath, $content, $flags=0){
		file_put_contents($filePath, $content, $flags);
	}
	
	public function fileOpen(Filepath $filePath, $mode){
		
		$handle = fopen($filePath, $mode);
		
		if(!is_resource($handle)){
			return null;
		}
		
		return $handle;
	}
	
	public function fileClose($handle){
		fclose($handle);
	}
	
	public function fileWrite($handle, $data){
		fwrite($handle, $data);
	}
	
	public function fileRead($handle, $length){
		fread($handle, $length);
	}
	
	public function fileTruncate($handle, $size){
		ftruncate($handle, $size);
	}
	
	public function fileSeek($handle, $offset){
		fseek($handle, $offset);
	}
	
	public function fileTell($handle){
		ftell($handle);
	}
	
	public function fileEOF($handle){
		return feof($handle);
	}
	
	public function fileReadLine($handle){
		return fgets($handle);
	}
	
	/**
	 * removes recursive a whole directory
	 * (copied from a comment in http://de.php.net/rmdir)
	 * 
	 * @package Addiks
	 * @subpackage External
	 * @author Someone else from the thing called internet (NOSPAMzentralplan dot de)
	 * @param string $dir
	 */
	static public function rrmdir($dir) {
 	
	 	if(!file_exists($dir)){
	 		return;
	 	}
	 	
	 	if(!is_writable($dir)){
	 		throw new Error("Cannot remove directory {$dir} because it is not writable to current user!");
	 	}
	 	
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					
					if (filetype($dir."/".$object) == "dir") {
						self::rrmdir($dir."/".$object); 
						
					}else {
						unlink($dir."/".$object);
					}
				}
			}
		 	reset($objects);
		 	rmdir($dir);
		}
	}
	
}