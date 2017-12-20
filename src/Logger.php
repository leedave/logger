<?php

namespace Leedch\Logger;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NativeMailerHandler;
use Leedch\Validator\Validator;

/**
 * Currently uses Monolog, because I really like it. I just use this class to 
 * extend some methods I often use
 * @author david.lee
 */
abstract class Logger extends MonoLogger
{
    protected $logFile;
    
    public function __construct(string $name) {
        parent::__construct($name);
    }
    
    /**
     * Set up a logger to write to logFile
     * @param string $logFile
     * @param int $logLevel
     */
    protected function setFileHandler(string $logFile, int $logLevel = null)
    {
        $this->logFile = $logFile;
        $stream = new StreamHandler($logFile, $logLevel);
        $this->pushHandler($stream);        
    }
    
    /**
     * Set up the logger to send an email
     * @param string $emailTo
     * @param string $emailSubject
     * @param string $emailFrom
     * @param int $logLevel
     */
    protected function setMailHandler(string $emailTo, string $emailSubject, string $emailFrom, int $logLevel)
    {
        $mail = new NativeMailerHandler($emailTo, $emailSubject, $emailFrom, $logLevel);
        $this->pushHandler($mail);
    }
    
    /**
     * Reads a logfile line by line and makes it into an array
     * @param int $limit
     * @return array
     */
    public function getLogs(int $limit = 0)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        $lineCount = 0;
        $fileAsc = file($this->logFile);
        $file = array_reverse($fileAsc);
        $arrOutput = [];
        foreach ($file as $row) {
            $currentRow = $this->makeLogRow($row);
            if (!$currentRow) {
                continue;
            }
            $arrOutput[] = $currentRow;
            $lineCount++;
            if ($lineCount > $limit && $limit > 0) {
                break;
            }
        }
        return $arrOutput;
    }
    
    /**
     * Creates an array from one log line
     * @param string $row
     * @return boolean|array
     */
    protected function makeLogRow(string $row)
    {
        //[2017-07-22 23:06:48]
        $date = substr($row, 1, 19);
        if (!Validator::validateDate($date)) {
            return false;
        }
        $fullMsg = substr($row, 22);
        $arrMsg = explode(":", $fullMsg);
        $logLevel = $arrMsg[0];
        $msg = substr($fullMsg, strpos($fullMsg, ":")+2);
        $out = [
            "date" => $date,
            "level" => $logLevel,
            "msg" => $msg,
        ];
        return $out;
    }
    
    /**
     * Empty the logFile. I just prefer the word flush, sounds more fun
     *  .__   .-".
     * (o\"\  |  |
     *    \_\ |  |
     *   _.---:_ |
     *  ("-..-" /
     *   "-.-" /
     *     /   |
     *     "--"  
     */
    public function flush()
    {
        $file = fopen($this->logFile, "w");
        fclose($file);
    }
}
