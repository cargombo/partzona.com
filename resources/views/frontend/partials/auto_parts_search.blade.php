<div class="auto-parts-search-section">
    <div class="search-container">
        <div class="search-header">
            <div class="search-icon-wrapper">
                <i class="las la-car"></i>
            </div>
            <h3 class="search-title">{{ translate('Find Auto Parts') }}</h3>
            <p class="search-subtitle">{{ translate('Search by brand, model, or part name') }}</p>
        </div>

        <form action="{{ route('auto_parts.search') }}" method="POST" id="autoPartsSearchForm">
            @csrf
            <div class="search-fields-wrapper">
                <!-- Brand Selection with Autocomplete -->
                <div class="search-field">
                    <div class="field-icon">
                        <i class="las la-trademark"></i>
                    </div>
                    <div class="field-content position-relative">
                        <label for="brand_search" class="field-label">{{ translate('Car Brand') }}</label>
                        <input type="text"
                               class="field-input"
                               id="brand_search"
                               placeholder="{{ translate('e.g. Mercedes, BMW, Toyota...') }}"
                               autocomplete="off">
                        <input type="hidden" name="brand_id" id="brand_id">
                        <div id="brand_suggestions" class="suggestions-dropdown"></div>
                    </div>
                </div>

                <!-- Model Selection (Initially Disabled) -->
                <div class="search-field">
                    <div class="field-icon">
                        <i class="las la-car-side"></i>
                    </div>
                    <div class="field-content">
                        <label for="model_select" class="field-label">{{ translate('Car Model') }}</label>
                        <select class="field-input field-select" name="model_id" id="model_select" disabled>
                            <option value="">{{ translate('First select a brand...') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Auto Part Selection with Autocomplete -->
                <div class="search-field">
                    <div class="field-icon">
                        <i class="las la-cog"></i>
                    </div>
                    <div class="field-content position-relative">
                        <label for="part_search" class="field-label">{{ translate('Auto Part') }}</label>
                        <input type="text"
                               class="field-input"
                               id="part_search"
                               placeholder="{{ translate('e.g. Engine Oil, Brake Pad...') }}"
                               autocomplete="off">
                        <input type="hidden" name="part_id" id="part_id">
                        <div id="part_suggestions" class="suggestions-dropdown"></div>
                    </div>
                </div>
            </div>

            <div class="search-actions">
                <button type="submit" class="btn-search">
                    <i class="las la-search"></i>
                    <span>{{ translate('Search Parts') }}</span>
                </button>
                <button type="button" class="btn-reset" id="resetSearchBtn">
                    <i class="las la-redo"></i>
                    <span>{{ translate('Reset') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.auto-parts-search-section {
    margin: 30px 0;
    padding: 0 15px;
}

.search-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.search-container::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.3; }
}

.search-header {
    text-align: center;
    margin-bottom: 35px;
    position: relative;
    z-index: 1;
}

.search-icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    margin-bottom: 15px;
    backdrop-filter: blur(10px);
}

.search-icon-wrapper i {
    font-size: 35px;
    color: white;
}

.search-title {
    color: white;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 10px 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 15px;
    margin: 0;
}

.search-fields-wrapper {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
    position: relative;
    z-index: 1;
}

.search-field {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    gap: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.search-field:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.field-icon {
    flex-shrink: 0;
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.field-icon i {
    font-size: 24px;
    color: white;
}

.field-content {
    flex: 1;
    min-width: 0;
}

.field-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.field-input {
    width: 100%;
    border: none;
    background: transparent;
    font-size: 16px;
    color: #333;
    outline: none;
    font-weight: 500;
}

.field-input::placeholder {
    color: #999;
    font-weight: 400;
}

.field-select {
    cursor: pointer;
    padding-right: 25px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23666'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0 center;
    background-size: 24px;
    appearance: none;
}

.field-select:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 8px;
    margin-top: 8px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    border: 1px solid #e0e0e0;
}

.suggestion-item {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
}

.suggestion-item:hover {
    background: linear-gradient(90deg, #f8f9ff 0%, #fff 100%);
    padding-left: 20px;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.search-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    position: relative;
    z-index: 1;
}

.btn-search, .btn-reset {
    padding: 15px 35px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-search {
    background: white;
    color: #667eea;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.25);
    background: #f8f9ff;
}

.btn-reset {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    backdrop-filter: blur(10px);
}

.btn-reset:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.btn-search i, .btn-reset i {
    font-size: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .search-container {
        padding: 25px 20px;
    }

    .search-fields-wrapper {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .search-actions {
        flex-direction: column;
    }

    .btn-search, .btn-reset {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
$(document).ready(function() {
    let brandSearchTimeout;
    let partSearchTimeout;

    // Brand Autocomplete
    $('#brand_search').on('input', function() {
        clearTimeout(brandSearchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#brand_suggestions').hide().empty();
            return;
        }

        brandSearchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{ route("api.search.brands") }}',
                data: { q: query },
                success: function(brands) {
                    $('#brand_suggestions').empty();

                    if (brands.length > 0) {
                        brands.forEach(function(brand) {
                            $('#brand_suggestions').append(
                                `<div class="suggestion-item" data-brand-id="${brand.id}" data-brand-name="${brand.name}">
                                    ${brand.name}
                                </div>`
                            );
                        });
                        $('#brand_suggestions').show();
                    } else {
                        $('#brand_suggestions').hide();
                    }
                }
            });
        }, 300);
    });

    // Handle Brand Selection
    $(document).on('click', '.suggestion-item[data-brand-id]', function() {
        const brandId = $(this).data('brand-id');
        const brandName = $(this).data('brand-name');

        $('#brand_search').val(brandName);
        $('#brand_id').val(brandId);
        $('#brand_suggestions').hide();

        // Load models for selected brand
        loadModels(brandId);
    });

    // Load Models by Brand
    function loadModels(brandId) {
        $('#model_select').prop('disabled', true).html('<option value="">{{ translate("Loading...") }}</option>');

        $.ajax({
            url: '{{ route("api.models.by_brand") }}',
            data: { brand_id: brandId },
            success: function(models) {
                $('#model_select').empty().append('<option value="">{{ translate("Select model...") }}</option>');

                if (models.length > 0) {
                    models.forEach(function(model) {
                        $('#model_select').append(`<option value="${model.id}">${model.name}</option>`);
                    });
                    $('#model_select').prop('disabled', false);
                } else {
                    $('#model_select').append('<option value="">{{ translate("No models found") }}</option>');
                }
            }
        });
    }

    // Auto Part Autocomplete
    $('#part_search').on('input', function() {
        clearTimeout(partSearchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#part_suggestions').hide().empty();
            return;
        }

        partSearchTimeout = setTimeout(function() {
            $.ajax({
                url: '{{ route("api.search.parts") }}',
                data: {
                    q: query,
                    lang: '{{ app()->getLocale() }}'
                },
                success: function(parts) {
                    $('#part_suggestions').empty();

                    if (parts.length > 0) {
                        parts.forEach(function(part) {
                            $('#part_suggestions').append(
                                `<div class="suggestion-item" data-part-id="${part.id}" data-part-name="${part.name}">
                                    <strong>${part.name}</strong>
                                    ${part.description ? '<br><small class="text-muted">' + part.description + '</small>' : ''}
                                </div>`
                            );
                        });
                        $('#part_suggestions').show();
                    } else {
                        $('#part_suggestions').hide();
                    }
                }
            });
        }, 300);
    });

    // Handle Part Selection
    $(document).on('click', '.suggestion-item[data-part-id]', function() {
        const partId = $(this).data('part-id');
        const partName = $(this).data('part-name');

        $('#part_search').val(partName);
        $('#part_id').val(partId);
        $('#part_suggestions').hide();
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#brand_search, #brand_suggestions').length) {
            $('#brand_suggestions').hide();
        }
        if (!$(e.target).closest('#part_search, #part_suggestions').length) {
            $('#part_suggestions').hide();
        }
    });

    // Reset Form
    $('#resetSearchBtn').on('click', function() {
        $('#autoPartsSearchForm')[0].reset();
        $('#brand_id, #part_id').val('');
        $('#model_select').prop('disabled', true).html('<option value="">{{ translate("Select model...") }}</option>');
        $('#brand_suggestions, #part_suggestions').hide().empty();
    });

    // Form Validation
    $('#autoPartsSearchForm').on('submit', function(e) {
        if (!$('#brand_id').val() && !$('#model_select').val() && !$('#part_id').val()) {
            e.preventDefault();
            alert('{{ translate("Please select at least one search criteria") }}');
            return false;
        }
    });
});
</script>
