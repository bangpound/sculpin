<?php

/*
 * This file is a part of Sculpin.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sculpin\Bundle\SculpinBundle\HttpServer;

use Dflydev\ApacheMimeTypes\PhpRepository;
use React\EventLoop\StreamSelectLoop;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use React\Http\Request;
use React\Http\Server as ReactHttpServer;
use React\Socket\Server as ReactSocketServer;

/**
 * HTTP Server
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class HttpServer implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string          $docroot Docroot
     * @param string          $env     Environment
     * @param bool            $debug   Debug
     * @param int             $port    Port
     */
    public function __construct($docroot, $env, $debug, $port = null)
    {
        $repository = new PhpRepository;

        if (!$port) {
            $port = 8000;
        }

        $this->env = $env;
        $this->debug = $debug;
        $this->port = $port;

        $this->loop = new StreamSelectLoop;
        $socketServer = new ReactSocketServer($this->loop);
        $httpServer = new ReactHttpServer($socketServer);
        $httpServer->on("request", function ($request, $response) use ($repository, $docroot, $output) {
            $path = $docroot.'/'.ltrim(rawurldecode($request->getPath()), '/');
            if (is_dir($path)) {
                $path .= '/index.html';
            }
            if (!file_exists($path)) {
                $this->logRequest(404, $request);
                $response->writeHead(404, [
                    'Content-Type' => 'text/html',
                ]);

                return $response->end(implode('', [
                    '<h1>404</h1>',
                    '<h2>Not Found</h2>',
                    '<p>',
                    'The embedded <a href="https://sculpin.io">Sculpin</a> web server could not find the requested resource.',
                    '</p>'
                ]));
            }

            $type = 'application/octet-stream';

            if ('' !== $extension = pathinfo($path, PATHINFO_EXTENSION)) {
                if ($guessedType = $repository->findType($extension)) {
                    $type = $guessedType;
                }
            }

            $this->logRequest(200, $request);

            $response->writeHead(200, array(
                "Content-Type" => $type,
            ));
            $response->end(file_get_contents($path));
        });

        $socketServer->listen($port, '0.0.0.0');
    }

    /**
     * Add a periodic timer
     *
     * @param int      $interval Interval
     * @param callable $callback Callback
     */
    public function addPeriodicTimer($interval, $callback)
    {
        $this->loop->addPeriodicTimer($interval, $callback);
    }

    /**
     * Run server
     */
    public function run()
    {


        $this->logger->alert(sprintf('Starting Sculpin server for the <info>%s</info> environment with debug <info>%s</info>', $this->env, var_export($this->debug, true)));
        $this->logger->alert(sprintf('Development server is running at <info>http://%s:%s</info>', 'localhost', $this->port));
        $this->logger->alert('Quit the server with CONTROL-C.');

        $this->loop->run();
    }

    /**
     * Log a request
     *
     * @param string          $responseCode Response code
     * @param Request         $request      Request
     */
    public function logRequest($responseCode, Request $request)
    {
        $wrapOpen = '';
        $wrapClose = '';
        if ($responseCode >= 400) {
            $wrapOpen = '<comment>';
            $wrapClose = '</comment>';
        }
        $this->logger->info($wrapOpen.sprintf('[%s] "%s %s HTTP/%s" %s', date('d/M/Y H:i:s'), $request->getMethod(), $request->getPath(), $request->getHttpVersion(), $responseCode).$wrapClose);
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     * @return null|void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
