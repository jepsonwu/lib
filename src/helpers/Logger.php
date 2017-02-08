<?php
namespace Jepsonwu\helpers;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Registry;

/**
 * Logger::getInstance()->setChannel(channel)->setFile(loggerFile)->addLogger();
 * LogInstance = Logger::getLogger(loggerFile);
 *
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2016/12/8
 * Time: 17:23
 */
class Logger
{
    const HANDLER_STREAM = 1;
    const HANDLER_FILE = 2;

    protected static $instance = null;

    protected $channel = "";
    protected $handler_type = self::HANDLER_FILE;
    protected $line_format = "[%datetime%] %level_name%: %message% %context% %extra%\n";

    protected $file = null;
    protected $max_files = 30;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        is_null(self::$instance) && self::$instance = new self();
        return self::$instance;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    public function getHandlerType()
    {
        return $this->handler_type;
    }

    public function setHandlerType($type)
    {
        $this->handler_type = $type;
        return $this;
    }

    public function getFile()
    {
        if (is_null($this->file)) {
            $this->file = "/tmp/tmp";
        }

        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    public function getMaxFiles()
    {
        return $this->max_files;
    }

    public function setMaxFiles($num)
    {
        $this->max_files = (int)$num;
        return $this;
    }

    public function getLineFormat()
    {
        return $this->line_format;
    }

    public function setLineFormat($format)
    {
        $this->line_format = $format;
        return $this;
    }

    protected function getHandler()
    {
        switch ($this->getHandlerType()) {
            case self::HANDLER_STREAM:
                $handler = new StreamHandler("php://stdout");
                break;
            case self::HANDLER_FILE:
            default:
                $handler = new RotatingFileHandler($this->getFile(), $this->getMaxFiles());
                break;
        }

        $lineFormat = new LineFormatter($this->getLineFormat());
        $lineFormat->allowInlineLineBreaks();
        $lineFormat->includeStacktraces();
        $handler->setFormatter($lineFormat);

        return $handler;
    }

    /**
     * @param $channel
     * @return \Monolog\Logger
     */
    public static function getLogger($channel)
    {
        return Registry::getInstance($channel);
    }

    public function addLogger()
    {
        if (!Registry::hasLogger($this->getChannel())) {
            $logger = new \Monolog\Logger($this->getChannel());
            $logger->pushHandler($this->getHandler());
            Registry::addLogger($logger);
        }

        return true;
    }
}