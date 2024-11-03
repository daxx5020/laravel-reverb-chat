<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'mediaable_id',
        'mediaable_type',
        'file_path',
        'file_type',
        'mime_type',
        'size',
    ];

    /**
     * Define a polymorphic relationship.
     */
    public function mediaable()
    {
        return $this->morphTo();
    }

    /**
     * Accessor for the full file URL based on file type.
     */
    public function getUrlAttribute()
    {
        $directory = match ($this->file_type) {
            'image' => 'chat_images',
            'video' => 'chat_videos',
            'document' => 'chat_documents',
            default => 'media',
        };

        return asset("storage/{$directory}/" . $this->file_path);
    }

    /**
     * Store the uploaded file based on file type and return the stored path.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $fileType
     * @return string
     */
    public static function storeFile($file, $fileType)
    {
        // Determine directory based on file type
        $directory = match ($fileType) {
            'image' => '`chat_images`',
            'video' => 'chat_videos',
            'document' => 'chat_documents',
            default => 'media',
        };

        // Store file and return the path
        return $file->store($directory, 'public');
    }

    /**
     * Helper method to check if the media is an image.
     */
    public function isImage()
    {
        return $this->file_type === 'image';
    }

    /**
     * Helper method to check if the media is a video.
     */
    public function isVideo()
    {
        return $this->file_type === 'video';
    }

    /**
     * Helper method to check if the media is a document.
     */
    public function isDocument()
    {
        return $this->file_type === 'document';
    }
}
