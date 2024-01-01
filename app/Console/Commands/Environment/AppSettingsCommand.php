<?php

namespace Pterodactyl\Console\Commands\Environment;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;

class AppSettingsCommand extends Command
{
    use EnvironmentWriterTrait;

    public const CACHE_DRIVERS = [
        'redis' => 'Redis（推奨）',
        'memcached' => 'Memcached',
        'file' => 'ファイルシステム',
    ];

    public const SESSION_DRIVERS = [
        'redis' => 'Redis（推奨）',
        'memcached' => 'Memcached',
        'database' => 'MySQLデータベース',
        'file' => 'ファイルシステム',
        'cookie' => 'Cookie',
    ];

    public const QUEUE_DRIVERS = [
        'redis' => 'Redis（推奨）',
        'database' => 'MySQLデータベース',
        'sync' => 'Sync',
    ];

    protected $description = 'Panel の基本的な環境設定を行います。';

    protected $signature = 'p:environment:setup
                            {--new-salt : Whether or not to generate a new salt for Hashids.}
                            {--author= : The email that services created on this instance should be linked to.}
                            {--url= : The URL that this Panel is running on.}
                            {--timezone= : The timezone to use for Panel times.}
                            {--cache= : The cache driver backend to use.}
                            {--session= : The session driver backend to use.}
                            {--queue= : The queue driver backend to use.}
                            {--redis-host= : Redis host to use for connections.}
                            {--redis-pass= : Password used to connect to redis.}
                            {--redis-port= : Port to connect to redis over.}
                            {--settings-ui= : Enable or disable the settings UI.}
                            {--telemetry= : Enable or disable anonymous telemetry.}';

    protected array $variables = [];

    /**
     * AppSettingsCommand constructor.
     */
    public function __construct(private Kernel $console)
    {
        parent::__construct();
    }

    /**
     * Handle command execution.
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function handle(): int
    {
        if (empty(config('hashids.salt')) || $this->option('new-salt')) {
            $this->variables['HASHIDS_SALT'] = str_random(20);
        }

        $this->output->comment('この Panel からエクスポートされる卵の送信元メールアドレスを指定します。有効なメールアドレスでなければなりません。');
        $this->variables['APP_SERVICE_AUTHOR'] = $this->option('author') ?? $this->ask(
            'Egg Author Email',
            config('pterodactyl.service.author', 'unknown@unknown.com')
        );

        if (!filter_var($this->variables['APP_SERVICE_AUTHOR'], FILTER_VALIDATE_EMAIL)) {
            $this->output->error('The service author email provided is invalid.');

            return 1;
        }

        $this->output->comment('アプリケーションのURLは、SSLを使用しているかどうかに応じて、https:// または http:// で始めなければなりません。このスキームを含めないと、メールやその他のコンテンツが間違った場所にリンクされてしまいます。');
        $this->variables['APP_URL'] = $this->option('url') ?? $this->ask(
            'Application URL',
            config('app.url', 'https://example.com')
        );

        $this->output->comment('timezoneはPHPがサポートしているtimezoneのいずれかと一致する必要があります。不明な場合は https://php.net/manual/en/timezones.php を参照ください。');
        $this->variables['APP_TIMEZONE'] = $this->option('timezone') ?? $this->anticipate(
            'Application Timezone',
            \DateTimeZone::listIdentifiers(),
            config('app.timezone')
        );

        $selected = config('cache.default', 'redis');
        $this->variables['CACHE_DRIVER'] = $this->option('cache') ?? $this->choice(
            'Cache Driver',
            self::CACHE_DRIVERS,
            array_key_exists($selected, self::CACHE_DRIVERS) ? $selected : null
        );

        $selected = config('session.driver', 'redis');
        $this->variables['SESSION_DRIVER'] = $this->option('session') ?? $this->choice(
            'Session Driver',
            self::SESSION_DRIVERS,
            array_key_exists($selected, self::SESSION_DRIVERS) ? $selected : null
        );

        $selected = config('queue.default', 'redis');
        $this->variables['QUEUE_CONNECTION'] = $this->option('queue') ?? $this->choice(
            'Queue Driver',
            self::QUEUE_DRIVERS,
            array_key_exists($selected, self::QUEUE_DRIVERS) ? $selected : null
        );

        if (!is_null($this->option('settings-ui'))) {
            $this->variables['APP_ENVIRONMENT_ONLY'] = $this->option('settings-ui') == 'true' ? 'false' : 'true';
        } else {
            $this->variables['APP_ENVIRONMENT_ONLY'] = $this->confirm('Enable UI based settings editor?', true) ? 'false' : 'true';
        }

        $this->output->comment('テレメトリーデータと収集に関するより詳細な情報については、https://pterodactyl.io/panel/1.0/additional_configuration.html#telemetry。');
        $this->variables['PTERODACTYL_TELEMETRY_ENABLED'] = $this->option('telemetry') ?? $this->confirm(
            '匿名の遠隔測定データの送信を有効にするか？',
            config('pterodactyl.telemetry.enabled', true)
        ) ? 'true' : 'false';

        // Make sure session cookies are set as "secure" when using HTTPS
        if (str_starts_with($this->variables['APP_URL'], 'https://')) {
            $this->variables['SESSION_SECURE_COOKIE'] = 'true';
        }

        $this->checkForRedis();
        $this->writeToEnvironment($this->variables);

        $this->info($this->console->output());

        return 0;
    }

    /**
     * Check if redis is selected, if so, request connection details and verify them.
     */
    private function checkForRedis()
    {
        $items = collect($this->variables)->filter(function ($item) {
            return $item === 'redis';
        });

        // Redis was not selected, no need to continue.
        if (count($items) === 0) {
            return;
        }

        $this->output->note('1つ以上のオプションでRedisドライバを選択したので、以下に有効な接続情報を入力してください。ほとんどの場合、設定を変更していない限り、提供されたデフォルトを使用できます。');
        $this->variables['REDIS_HOST'] = $this->option('redis-host') ?? $this->ask(
            'Redis Host',
            config('database.redis.default.host')
        );

        $askForRedisPassword = true;
        if (!empty(config('database.redis.default.password'))) {
            $this->variables['REDIS_PASSWORD'] = config('database.redis.default.password');
            $askForRedisPassword = $this->confirm('Redisにはすでにパスワードが定義されているようですが、変更しますか？');
        }

        if ($askForRedisPassword) {
            $this->output->comment('デフォルトでは、Redisサーバー・インスタンスはローカルで実行され、外部からはアクセスできないため、パスワードは設定されていない。この場合は、値を入力せずにEnterを押すだけです。');
            $this->variables['REDIS_PASSWORD'] = $this->option('redis-pass') ?? $this->output->askHidden(
                'Redisのパスワード'
            );
        }

        if (empty($this->variables['REDIS_PASSWORD'])) {
            $this->variables['REDIS_PASSWORD'] = 'null';
        }

        $this->variables['REDIS_PORT'] = $this->option('redis-port') ?? $this->ask(
            'Redisポート',
            config('database.redis.default.port')
        );
    }
}
