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

namespace Addiks\PHPSQL\Filesystem;

use Addiks\PHPSQL\Filesystem;

interface FilesystemInterface
{
    
    public function getFileContents($filePath);
    
    public function putFileContents($filePath, $content, $flags = 0);
    
    public function getFile($filePath, $mode);

    public function fileOpen($filePath, $mode);
    
    public function fileClose($handle);
    
    public function fileWrite($handle, $data);
    
    public function fileRead($handle, $length);
    
    public function fileTruncate($handle, $size);
    
    public function fileSeek($handle, $offset, $seekMode = SEEK_SET);
    
    public function fileTell($handle);
    
    public function fileEOF($handle);
    
    public function fileReadLine($handle);
    
    public function fileUnlink($filePath);

    public function fileSize($filePath);

    public function fileIsDir($path);

    public function fileExists($filePath);

    public function getFilesInDir($path);

    /**
     * @return DirectoryIterator
     */
    public function getDirectoryIterator($path);

    /**
     * removes recursive a whole directory
     *
     * @param string $dir
     */
    public static function rrmdir($dir);
}