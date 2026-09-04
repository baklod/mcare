<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhilippineAddressLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_form_uses_address_selects_backed_by_lookup_routes(): void
    {
        $this->withSession([
            'enrollment.admission_application_id' => $this->makeApprovedAdmission()->id,
        ])->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('name="region"', false)
            ->assertSee('<select id="region"', false)
            ->assertSee('<select id="province"', false)
            ->assertSee('<select id="city"', false)
            ->assertSee('<select id="barangay"', false)
            ->assertSee(route('enrollment.address.regions', absolute: false), false)
            ->assertSee('Choose region first', false)
            ->assertSee('data-copy-address-to-birthplace', false)
            ->assertSee('Use my permanent address as my birthplace');
    }

    public function test_alumni_claim_form_uses_the_same_address_lookup_selects(): void
    {
        $this->get(route('alumni.claim.create'))
            ->assertOk()
            ->assertSee('name="region"', false)
            ->assertSee('id="region"', false)
            ->assertSee('data-address-field="region"', false)
            ->assertSee('data-address-field="province"', false)
            ->assertSee('data-address-field="city"', false)
            ->assertSee('data-address-field="barangay"', false)
            ->assertSee(route('enrollment.address.regions'), false)
            ->assertSee('Choose region first', false);
    }

    public function test_regions_are_listed_with_official_labels(): void
    {
        $this->fakeSuccessfulGeographicApi();

        $this->getJson(route('enrollment.address.regions'))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Bicol Region')
            ->assertJsonPath('data.0.label', 'Bicol Region — Region V')
            ->assertJsonPath('data.1.name', 'NCR')
            ->assertJsonPath('data.1.code', '130000000');
    }

    public function test_provinces_are_filtered_by_region(): void
    {
        $this->fakeSuccessfulGeographicApi();

        $this->getJson(route('enrollment.address.provinces', ['region_code' => '050000000']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Albay')
            ->assertJsonPath('data.1.name', 'Camarines Sur');
    }

    public function test_ncr_exposes_metro_manila_so_cities_can_load_from_the_region(): void
    {
        $this->fakeSuccessfulGeographicApi();

        $this->getJson(route('enrollment.address.provinces', ['region_code' => '130000000']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Metro Manila')
            ->assertJsonPath('data.0.code', '130000000')
            ->assertJsonPath('data.0.city_parent', 'region');

        $this->getJson(route('enrollment.address.cities', ['region_code' => '130000000']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Quezon City');
    }

    public function test_cities_and_barangays_follow_the_selected_parent(): void
    {
        $this->fakeSuccessfulGeographicApi();

        $this->getJson(route('enrollment.address.cities', ['province_code' => '051700000']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Naga City');

        $this->getJson(route('enrollment.address.barangays', ['city_code' => '137404000']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Central');
    }

    public function test_invalid_or_missing_codes_are_rejected(): void
    {
        $this->getJson(route('enrollment.address.provinces', ['region_code' => 'not-a-code']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('region_code');

        $this->getJson(route('enrollment.address.cities'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['province_code', 'region_code']);

        $this->getJson(route('enrollment.address.barangays'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city_code');
    }

    public function test_opening_an_address_lookup_in_the_browser_returns_to_enrollment(): void
    {
        $this->get(route('enrollment.address.regions'))
            ->assertRedirect(route('enrollment.create'));

        $this->get(route('enrollment.address.barangays', ['city_code' => '137404000']))
            ->assertRedirect(route('enrollment.create'));
    }

    public function test_upstream_failures_are_reported_without_leaking_details(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://psgc.gitlab.io/api/regions/' => Http::response('unavailable', 500),
        ]);

        $this->getJson(route('enrollment.address.regions'))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Address lookup is temporarily unavailable. Please try again.');
    }

    private function fakeSuccessfulGeographicApi(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $url = rtrim($request->url(), '/');

            return match ($url) {
                'https://psgc.gitlab.io/api/regions' => Http::response([
                    [
                        'code' => '050000000',
                        'name' => 'Bicol Region',
                        'regionName' => 'Region V',
                    ],
                    [
                        'code' => '130000000',
                        'name' => 'NCR',
                        'regionName' => 'National Capital Region',
                    ],
                ]),
                'https://psgc.gitlab.io/api/regions/050000000/provinces' => Http::response([
                    ['code' => '051700000', 'name' => 'Camarines Sur'],
                    ['code' => '050500000', 'name' => 'Albay'],
                ]),
                'https://psgc.gitlab.io/api/regions/130000000/provinces' => Http::response([]),
                'https://psgc.gitlab.io/api/regions/130000000/cities-municipalities' => Http::response([
                    ['code' => '137404000', 'name' => 'Quezon City'],
                ]),
                'https://psgc.gitlab.io/api/provinces/051700000/cities-municipalities' => Http::response([
                    ['code' => '051724000', 'name' => 'Naga City'],
                ]),
                'https://psgc.gitlab.io/api/cities-municipalities/137404000/barangays' => Http::response([
                    ['code' => '137404020', 'name' => 'Central'],
                ]),
                default => Http::response(['unmocked' => $request->url()], 500),
            };
        });
    }
}
