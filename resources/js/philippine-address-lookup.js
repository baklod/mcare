export function attachPhilippineAddressLookups() {
    document.querySelectorAll('[data-address-regions-url]').forEach(attachPhilippineAddressLookup);
}

function attachPhilippineAddressLookup(form) {
    const regionSelect = form.querySelector('[data-address-field="region"]');
    const provinceSelect = form.querySelector('[data-address-field="province"]');
    const citySelect = form.querySelector('[data-address-field="city"]');
    const barangaySelect = form.querySelector('[data-address-field="barangay"]');
    const status = form.querySelector('[data-address-lookup-status]');

    if (!regionSelect || !provinceSelect || !citySelect || !barangaySelect) return;

    const endpoints = {
        regions: form.dataset.addressRegionsUrl || '',
        provinces: form.dataset.addressProvincesUrl || '',
        cities: form.dataset.addressCitiesUrl || '',
        barangays: form.dataset.addressBarangaysUrl || '',
    };
    const saved = {
        region: (form.dataset.addressRegion || '').trim(),
        province: (form.dataset.addressProvince || '').trim(),
        city: (form.dataset.addressCity || '').trim(),
        barangay: (form.dataset.addressBarangay || '').trim(),
    };
    const controllers = { regions: null, provinces: null, cities: null, barangays: null };

    function setStatus(message, isError) {
        if (!status) return;
        status.textContent = message || '';
        status.classList.toggle('text-red-600', Boolean(isError && message));
        status.classList.toggle('text-slate-500', !isError);
        status.classList.toggle('form-error', Boolean(isError && message));
    }

    function selectedOption(select) {
        return select.options[select.selectedIndex] || null;
    }

    function resetSelect(select, placeholder) {
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        select.appendChild(option);
        select.value = '';
    }

    function namesMatch(optionName, savedName) {
        if (!savedName) return false;
        if (optionName === savedName) return true;

        const normalize = (value) => value.trim().toLocaleLowerCase();
        const aliases = {
            'metro manila': ['ncr', 'national capital region', 'metro manila'],
            ncr: ['ncr', 'national capital region', 'metro manila'],
        };

        return (aliases[normalize(optionName)] || []).includes(normalize(savedName));
    }

    function fillSelect(select, items, placeholder, savedName) {
        resetSelect(select, placeholder);
        let matched = false;

        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.name || '';
            option.textContent = item.label || item.name || '';
            option.dataset.code = item.code || '';
            option.dataset.cityParent = item.city_parent || '';
            if (!matched && namesMatch(option.value, savedName)) {
                option.selected = true;
                matched = true;
            }
            select.appendChild(option);
        });

        if (!matched && savedName) {
            const option = document.createElement('option');
            option.value = savedName;
            option.textContent = savedName;
            option.selected = true;
            select.appendChild(option);
        } else if (!matched && items.length === 1) {
            select.selectedIndex = 1;
        }

        return selectedOption(select);
    }

    function lookupUrl(base, params) {
        const url = new URL(base, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value) url.searchParams.set(key, value);
        });
        return url.toString();
    }

    async function fetchItems(key, url) {
        controllers[key]?.abort();
        controllers[key] = new AbortController();

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controllers[key].signal,
        });

        if (!response.ok) {
            throw new Error('Address lookup is temporarily unavailable. Please refresh and try again.');
        }

        const payload = await response.json();
        return Array.isArray(payload.data) ? payload.data : [];
    }

    async function loadRegions() {
        if (!endpoints.regions) return;

        setStatus('Loading regions…');
        try {
            const items = await fetchItems('regions', endpoints.regions);
            fillSelect(regionSelect, items, 'Select region', saved.region);
            setStatus('');
            form.dispatchEvent(new Event('address-lookup-updated'));
            if (selectedOption(regionSelect)?.dataset.code) {
                await loadProvinces({ userChange: false });
            }
        } catch (error) {
            if (error?.name === 'AbortError') return;
            resetSelect(regionSelect, 'Select region');
            setStatus(error.message || 'Address lookup is temporarily unavailable.', true);
        }
    }

    async function loadProvinces({ userChange }) {
        const regionOption = selectedOption(regionSelect);
        const regionCode = regionOption?.dataset.code || '';

        if (userChange) {
            saved.province = '';
            saved.city = '';
            saved.barangay = '';
        }

        // Reset the province select immediately so a stale option cannot be
        // submitted while the new province list is still being fetched. Do
        // the same for city and barangay because they depend on province.
        resetSelect(provinceSelect, 'Loading provinces…');
        resetSelect(citySelect, 'Select province first');
        resetSelect(barangaySelect, 'Select city first');

        if (!regionCode || !endpoints.provinces) {
            resetSelect(provinceSelect, 'Select region first');
            return;
        }

        setStatus('Loading provinces…');
        try {
            const items = await fetchItems('provinces', lookupUrl(endpoints.provinces, { region_code: regionCode }));
            const selected = fillSelect(provinceSelect, items, 'Select province', saved.province);
            setStatus('');
            form.dispatchEvent(new Event('address-lookup-updated'));

            if (selected?.dataset.code || selected?.value) {
                await loadCities({ userChange: false });
            }
        } catch (error) {
            if (error?.name === 'AbortError') return;
            resetSelect(provinceSelect, 'Select province');
            setStatus(error.message || 'Address lookup is temporarily unavailable.', true);
        }
    }

    async function loadCities({ userChange }) {
        const provinceOption = selectedOption(provinceSelect);
        const regionOption = selectedOption(regionSelect);
        const provinceCode = provinceOption?.dataset.code || '';
        const regionCode = regionOption?.dataset.code || '';

        if (userChange) {
            saved.city = '';
            saved.barangay = '';
        }

        resetSelect(citySelect, 'Loading cities…');
        resetSelect(barangaySelect, 'Select city first');

        if (!endpoints.cities || (!provinceCode && !regionCode)) {
            resetSelect(citySelect, 'Select province first');
            return;
        }

        const params = provinceOption?.dataset.cityParent === 'region'
            ? { region_code: regionCode }
            : { province_code: provinceCode };

        setStatus('Loading cities and municipalities…');
        try {
            const items = await fetchItems('cities', lookupUrl(endpoints.cities, params));
            const selected = fillSelect(citySelect, items, 'Select city/municipality', saved.city);
            setStatus('');
            form.dispatchEvent(new Event('address-lookup-updated'));

            if (selected?.dataset.code) {
                await loadBarangays({ userChange: false });
            }
        } catch (error) {
            if (error?.name === 'AbortError') return;
            resetSelect(citySelect, 'Select city/municipality');
            setStatus(error.message || 'Address lookup is temporarily unavailable.', true);
        }
    }

    async function loadBarangays({ userChange }) {
        const cityOption = selectedOption(citySelect);
        const cityCode = cityOption?.dataset.code || '';

        if (userChange) {
            saved.barangay = '';
        }

        resetSelect(barangaySelect, 'Loading barangays…');

        if (!cityCode || !endpoints.barangays) {
            resetSelect(barangaySelect, 'Select city first');
            return;
        }

        setStatus('Loading barangays…');
        try {
            const items = await fetchItems('barangays', lookupUrl(endpoints.barangays, { city_code: cityCode }));
            fillSelect(barangaySelect, items, 'Select barangay', saved.barangay);
            setStatus('');
        } catch (error) {
            if (error?.name === 'AbortError') return;
            resetSelect(barangaySelect, 'Select barangay');
            setStatus(error.message || 'Address lookup is temporarily unavailable.', true);
        }
    }

    regionSelect.addEventListener('change', () => {
        loadProvinces({ userChange: true });
    });
    provinceSelect.addEventListener('change', () => {
        loadCities({ userChange: true });
    });
    citySelect.addEventListener('change', () => {
        loadBarangays({ userChange: true });
    });

    loadRegions();
}
