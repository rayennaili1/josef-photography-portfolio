<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function index()
    {
        return Photo::with('album')->latest()->get()->shuffle();
    }

    public function byCategory($category)
    {
        return Photo::where('category', $category)->with('album')->latest()->get();
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'category' => 'required|string',
                'album_id' => 'nullable|exists:albums,id',
                'title' => 'nullable|string',
            ]);

            // Native GD WebP Conversion
            $imageFile = $request->file('image');
            $imageString = file_get_contents($imageFile->getRealPath());
            $image = @\imagecreatefromstring($imageString);
            
            $webpData = null;
            if ($image) {
                ob_start();
                \imagewebp($image, null, 80);
                $webpData = ob_get_clean();
                \imagedestroy($image);
            } else {
                $webpData = $imageString;
            }

            $cloudName = env('CLOUDINARY_CLOUD_NAME');
            $apiKey = env('CLOUDINARY_API_KEY');
            $apiSecret = env('CLOUDINARY_API_SECRET');

            if ($cloudName && $apiKey && $apiSecret) {
                // --- UPLOAD TO CLOUDINARY (For Render/Stateless Hosts) ---
                $params = [
                    'folder' => 'josef-photography',
                    'timestamp' => time(),
                ];
                
                // Sort parameters alphabetically (Cloudinary requirement)
                ksort($params);
                
                $signString = "";
                foreach ($params as $key => $val) {
                    $signString .= "$key=$val&";
                }
                $signString = rtrim($signString, '&');
                $signature = sha1($signString . $apiSecret);
                
                $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
                $postFields = array_merge($params, [
                    'file' => 'data:image/webp;base64,' . base64_encode($webpData),
                    'api_key' => $apiKey,
                    'signature' => $signature,
                ]);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                // Passing $postFields as an array automatically uses multipart/form-data, 
                // which is much better for large base64 strings than http_build_query.
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increase timeout to 60s
                $exec = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    throw new \Exception('CURL Error: ' . $error);
                }

                $response = json_decode($exec, true);
                $path = $response['secure_url'] ?? null;
                
                if (!$path) {
                    \Log::error('Cloudinary Upload Failed', ['response' => $response]);
                    throw new \Exception('Cloudinary upload failed: ' . ($response['error']['message'] ?? 'Unknown error'));
                }
                
                $photo = Photo::create([
                    'url' => $path,
                    'category' => $request->category,
                    'album_id' => $request->album_id,
                    'title' => $request->title,
                ]);
            } else {
                // --- UPLOAD TO LOCAL STORAGE (Default fallback) ---
                if (!Storage::disk('public')->exists('photos')) {
                    Storage::disk('public')->makeDirectory('photos');
                }
                
                $filename = 'photos/' . uniqid() . '.webp';
                Storage::disk('public')->put($filename, $webpData);
                
                $photo = Photo::create([
                    'url' => $filename,
                    'category' => $request->category,
                    'album_id' => $request->album_id,
                    'title' => $request->title,
                ]);
            }

            return response()->json($photo, 201);
        } catch (\Exception $e) {
            \Log::error('Upload Exception: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'category' => 'required|in:portrait,event',
            'album_id' => 'nullable|exists:albums,id',
        ]);

        $photo = Photo::findOrFail($id);
        $photo->update($request->only(['title', 'category', 'album_id']));

        return response()->json($photo);
    }

    public function incrementView($id)
    {
        $photo = Photo::findOrFail($id);
        $photo->increment('views_count');
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $photo = Photo::findOrFail($id);
        $path = str_replace('/storage/', '', $photo->url);
        Storage::disk('public')->delete($path);
        $photo->delete();

        return response()->json(null, 204);
    }
}
