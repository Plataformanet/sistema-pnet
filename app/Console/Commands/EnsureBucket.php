<?php

namespace App\Console\Commands;

use Aws\S3\Exception\S3Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class EnsureBucket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bucket:ensure
                            {bucket? : Nome do bucket a criar (padrão: o bucket configurado no disco)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Garante que o bucket de armazenamento exista (idempotente). Seguro para rodar no deploy/CI.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $diskName = config('bucket.disk');
        $disk = Storage::disk($diskName);

        if (! $disk instanceof FilesystemAdapter || config("filesystems.disks.{$diskName}.driver") !== 's3') {
            $this->info("Disco '{$diskName}' não é S3; nada a fazer.");

            return self::SUCCESS;
        }

        $configuredBucket = config("filesystems.disks.{$diskName}.bucket");
        $bucket = $this->argument('bucket') ?: $configuredBucket;

        if (! $bucket) {
            $this->error("Bucket não configurado para o disco '{$diskName}'. Informe um nome: php artisan bucket:ensure meu-bucket");

            return self::FAILURE;
        }

        if ($bucket !== $configuredBucket) {
            $this->warn("Atenção: '{$bucket}' difere do bucket configurado ('{$configuredBucket}'). A aplicação só usará este bucket se você atualizar AWS_BUCKET no .env.");
        }

        $client = $disk->getClient();

        if ($client->doesBucketExist($bucket)) {
            $this->info("Bucket '{$bucket}' já existe.");

            return self::SUCCESS;
        }

        try {
            $client->createBucket(['Bucket' => $bucket]);
            $this->info("Bucket '{$bucket}' criado com sucesso.");

            return self::SUCCESS;
        } catch (S3Exception $e) {
            $this->error("Falha ao criar o bucket '{$bucket}': ".$e->getAwsErrorMessage());

            return self::FAILURE;
        }
    }
}
