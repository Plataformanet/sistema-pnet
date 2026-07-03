<?php

namespace App\Console\Commands;

use Aws\S3\Exception\S3Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class EnsureDriveBucket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drive:ensure-bucket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Garante que o bucket do Drive exista no storage (idempotente). Seguro para rodar no deploy/CI.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $diskName = config('drive.disk');
        $disk = Storage::disk($diskName);

        if (! $disk instanceof FilesystemAdapter || config("filesystems.disks.{$diskName}.driver") !== 's3') {
            $this->info("Disco '{$diskName}' não é S3; nada a fazer.");

            return self::SUCCESS;
        }

        $bucket = config("filesystems.disks.{$diskName}.bucket");

        if (! $bucket) {
            $this->error("Bucket não configurado para o disco '{$diskName}'.");

            return self::FAILURE;
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
