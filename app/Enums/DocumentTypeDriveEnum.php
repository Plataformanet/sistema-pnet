<?php

namespace App\Enums;

enum DocumentTypeDriveEnum: string
{
    case FOLDER = 'folder';
    case PDF = 'pdf';
    case WORD = 'docx';
    case EXCEL = 'xlsx';
    case TXT = 'txt';
    case JPG = 'jpg';
    case PNG = 'png';
    case ZIP = 'zip';
    case TAR = 'tar';

    public function getType(): string
    {
        return match ($this) {
            self::PDF => 'pdf',
            self::WORD => 'docx',
            self::EXCEL => 'xlsx',
            self::TXT => 'txt',
            self::JPG => 'jpg',
            self::PNG => 'png',
            self::ZIP => 'zip',
            self::TAR => 'tar',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::FOLDER => 'bx bxs-folder',
            self::PDF => 'bx bxs-file-pdf',
            self::WORD => 'bx bxs-file-doc',
            self::EXCEL => 'bx bxs-file-doc',
            self::TXT => 'bx bxs-file-txt',
            self::JPG => 'bx bxs-file-image',
            self::PNG => 'bx bxs-file-png',
            self::ZIP => 'bx bxs-file-archive',
            self::TAR => 'bx bxs-file-archive',
        };
    }

    public function getStyle(): string
    {
        return match ($this) {
            self::FOLDER => 'font-size: 1.6rem; color: #dfb41e',
            self::PDF => 'font-size: 1.6rem; color: red',
            self::WORD => 'font-size: 1.6rem; color: blue',
            self::EXCEL => 'font-size: 1.6rem; color: green',
            self::TXT => 'font-size: 1.6rem; color: #4285f4',
            self::JPG => 'font-size: 1.6rem; color: #ea4335',
            self::PNG => 'font-size: 1.6rem; color: #ea4335',
            self::ZIP => 'font-size: 1.6rem; color: #5f6368',
            self::TAR => 'font-size: 1.6rem; color: #5f6368',
        };
    }
}
