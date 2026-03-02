<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\UserMeta;
use App\Models\UserAvatar;
use App\Models\UserWorld;
use App\Models\PlayerMeta;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $userMeta = $user->userMeta;

        $validated = $request->validate([
            'firstName' => 'nullable|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ]);

        $nameChanged = false;

        if (!empty($validated['firstName'])) {
            $userMeta->firstName = $validated['firstName'];
            $nameChanged = true;
        }

        if (!empty($validated['lastName'])) {
            $userMeta->lastName = $validated['lastName'];
            $nameChanged = true;
        }

        if ($nameChanged) {
            $user->name = $userMeta->firstName . ' ' . $userMeta->lastName;
            $user->save();
        }

        if (!empty($validated['current_password']) && !empty($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 422);
            }
            $user->password = Hash::make($validated['new_password']);
            $user->save();
        }

        $userMeta->save();

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'firstName' => $userMeta->firstName,
            'lastName' => $userMeta->lastName
        ]);
    }

    public function uploadProfilePicture(Request $request): JsonResponse
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $user = $request->user();
        $userMeta = $user->userMeta;

        try {
            $file = $request->file('profile_picture');

            $manager = new ImageManager(new GdDriver());
            $image = $manager->read($file)
                ->cover(50, 50)
                ->encode(new JpegEncoder(quality: 90));

            $filename = 'profile-pictures/' . $user->uid . '_' . time() . '.jpg';

            $disk = env('B2_ACCESS_KEY_ID') ? 'b2' : 'public';

            /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
            $storage = Storage::disk($disk);
            $storage->put($filename, (string) $image, ['visibility' => 'public']);

            $url = $storage->url($filename);

            if ($userMeta->profile_picture) {
                $this->deleteOldProfilePicture($userMeta->profile_picture, $disk, $user->uid);
            }

            $userMeta->profile_picture = $url;
            $userMeta->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'profile_picture' => $url
            ]);

        } catch (\Exception $e) {
            Log::error('Profile picture upload failed', [
                'user_id' => $user->uid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile picture. Please try again.'
            ], 500);
        }
    }

    private function deleteOldProfilePicture(string $oldUrl, string $disk, string $uid): void
    {
        if (preg_match('#(profile-pictures/' . preg_quote($uid, '#') . '_[^/]+\.jpg)#', $oldUrl, $matches)) {
            try {
                Storage::disk($disk)->delete($matches[1]);
            } catch (\Exception $e) {
                Log::warning('Failed to delete old profile picture', [
                    'user_id' => $uid,
                    'path' => $matches[1],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function deleteProfilePicture(Request $request): JsonResponse
    {
        $user = $request->user();
        $userMeta = $user->userMeta;

        try {
            if ($userMeta->profile_picture) {
                $disk = env('B2_ACCESS_KEY_ID') ? 'b2' : 'public';
                $this->deleteOldProfilePicture($userMeta->profile_picture, $disk, $user->uid);

                $userMeta->profile_picture = null;
                $userMeta->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile picture removed'
            ]);

        } catch (\Exception $e) {
            Log::error('Profile picture deletion failed', [
                'user_id' => $user->uid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove profile picture. Please try again.'
            ], 500);
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        DB::transaction(function () use ($user) {
            $neighborEntries = PlayerMeta::where('meta_key', 'like', '%_neighbors')
            ->where('meta_value', 'like', '%' . $user->uid . '%')->get();

            UserMeta::where('uid', '=', $user->uid)->delete();
            UserAvatar::where('uid', '=', $user->uid)->delete();
            UserWorld::where('uid', '=', $user->uid)->delete();
            PlayerMeta::where('uid', '=', $user->uid)->delete();
            
            $neighborEntries->each(function (PlayerMeta $entry) use ($user) {
                $neighbors = unserialize($entry->meta_value, ['allowed_classes' => false]);

                if (!is_array($neighbors)) {
                    return;
                }

                $neighbors = array_values(array_diff($neighbors, [(string) $user->uid]));
                $entry->meta_value = serialize($neighbors);
                $entry->save();
            });

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
