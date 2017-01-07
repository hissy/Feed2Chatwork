<?php
namespace Hissy\Feed2Chatwork\Command;

use Cake\Chronos\Chronos;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use PicoFeed\Parser\Item;
use PicoFeed\Reader\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrateCommand extends Command
{
    const API_BASE_URI = 'https://api.chatwork.com';

    protected function configure()
    {
        $this
            ->setName('feed2chatwork:integrate')
            ->setDescription('Push notification to chatwork from rss feeds.')
            ->addOption('config-file', 'f', InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = __DIR__ . '/../../config.json';
        if ($input->getOption('config-file')) {
            $file = $input->getOption('config-file');
        }

        $filesystem = new Filesystem();
        $config = new Repository(json_decode($filesystem->get($file), true));

        $default_timezone = ($config->get('timezone')) ? $config->get('timezone') : 'GMT';
        date_default_timezone_set($default_timezone);

        $last_fetched = new Chronos($config->get('last_fetched'));
        $now = Chronos::now();

        $client = new Client([
            'base_uri' => self::API_BASE_URI,
        ]);

        foreach ($config->get('feeds') as $setting) {
            $reader = new Reader();
            $resource = $reader->download($setting['feed_url']);

            $parser = $reader->getParser(
                $resource->getUrl(),
                $resource->getContent(),
                $resource->getEncoding()
            );

            $feed = $parser->execute();
            $items = $feed->getItems();

            /** @var Item $item */
            foreach ($items as $item) {
                $date = new Chronos($item->getDate()->format('Y-m-d H:i:s.u'), $item->getDate()->getTimezone());
                if ($date->gt($last_fetched)) {
                    $client->post(
                        sprintf('/v1/rooms/%d/messages', $setting['room_id']),
                        [
                            'headers' => [
                                'X-ChatWorkToken' => $config->get('chatwork.token')
                            ],
                            'form_params' => [
                                'body' => sprintf(
                                    '[info][title]%s[/title]%s[hr]%s[/info]',
                                    $item->getTitle(),
                                    strip_tags($item->getContent()),
                                    $item->getUrl()
                                )
                            ]
                        ]
                    );
                }
            }
        }

        if ($filesystem->isWritable($file)) {
            $config->set('last_fetched', (string)$now);
            $filesystem->put($file, json_encode($config->all(), JSON_PRETTY_PRINT));
        }
    }
}