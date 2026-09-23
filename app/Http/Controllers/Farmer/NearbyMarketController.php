<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\District;
use App\Services\Location\Contracts\GeocoderInterface;
use App\Services\Location\NearbyMarketService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NearbyMarketController extends Controller
{
    public function __construct(
        protected NearbyMarketService $nearbyService,
        protected GeocoderInterface $geocoder
    ) {}

    /**
     * Display the nearby APMC markets discovery interface.
     */
    public function index(Request $request): View
    {
        $lat = $request->query('lat');
        $lon = $request->query('lon');
        $districtId = $request->query('district');
        $radiusKm = (float) $request->query('radius', 50);
        $cropSlug = $request->query('crop');

        // All Karnataka districts for the manual location picker
        $allDistricts = District::whereHas('state', fn ($s) => $s->where('code', 'KA')->orWhere('name', 'Karnataka'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Major & active crops for the commodity filter dropdown
        $allCrops = Crop::where('is_active', true)
            ->orderBy('is_major', 'desc')
            ->orderBy('name')
            ->get();

        $userLat = null;
        $userLon = null;
        $locationName = null;
        $isGpsLocation = false;

        if ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            $userLat = (float) $lat;
            $userLon = (float) $lon;
            $isGpsLocation = true;

            // Reverse geocode to get human-readable locality
            $geocoded = $this->geocoder->reverseGeocode($userLat, $userLon);
            if ($geocoded) {
                $locationParts = array_filter([
                    $geocoded['locality'],
                    $geocoded['taluk'],
                    $geocoded['district'],
                ]);
                $locationName = implode(', ', array_unique($locationParts));
            }

            if (!$locationName) {
                $locationName = 'ನಿಮ್ಮ ಪ್ರಸ್ತುತ ಸ್ಥಳ (Your Location)';
            }
        } elseif ($districtId) {
            $selectedDistrict = $allDistricts->firstWhere('id', $districtId);
            if ($selectedDistrict && $selectedDistrict->latitude && $selectedDistrict->longitude) {
                $userLat = (float) $selectedDistrict->latitude;
                $userLon = (float) $selectedDistrict->longitude;
                $locationName = $selectedDistrict->name . ($selectedDistrict->name_kn ? ' (' . $selectedDistrict->name_kn . ')' : '');
            }
        }

        // Fallback default if neither GPS nor district specified: use Shivamogga coordinates
        if ($userLat === null || $userLon === null) {
            $defaultDistrict = $allDistricts->firstWhere('name', 'Shivamogga') ?? $allDistricts->first();
            if ($defaultDistrict) {
                $userLat = (float) $defaultDistrict->latitude;
                $userLon = (float) $defaultDistrict->longitude;
                $locationName = $defaultDistrict->name . ' (ಡೀಫಾಲ್ಟ್ ಕೇಂದ್ರ)';
                $districtId = $defaultDistrict->id;
            } else {
                $userLat = 13.9299;
                $userLon = 75.5681;
                $locationName = 'ಕರ್ನಾಟಕ';
            }
        }

        // Discover nearby Karnataka markets
        $nearbyMarkets = $this->nearbyService->findNearby(
            $userLat,
            $userLon,
            $radiusKm,
            $cropSlug,
            25
        );

        $selectedCrop = $cropSlug ? $allCrops->firstWhere('slug', $cropSlug) : null;

        return view('farmer.markets.nearby', compact(
            'nearbyMarkets',
            'userLat',
            'userLon',
            'locationName',
            'isGpsLocation',
            'radiusKm',
            'districtId',
            'cropSlug',
            'selectedCrop',
            'allDistricts',
            'allCrops'
        ));
    }
}
