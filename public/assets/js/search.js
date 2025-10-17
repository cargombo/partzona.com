/**
 * Auto Parts Search System
 * Version: 2.3.0
 * Last Updated: 2025-10-16
 *
 * Features:
 * - Brand autocomplete with ID tracking
 * - Model selection based on brand
 * - Auto part autocomplete with ID tracking
 * - Dual data submission (names + IDs)
 * - Header search functionality
 * - Form validation
 * - Debug logging for troubleshooting
 */

// Header Search Functionality
$(document).ready(function () {
    console.log('Header search script loaded - v2.3.0');
    let brandSearchTimeout;
    let partSearchTimeout;

    // Brand Autocomplete for Header Search
    $('#header_brand_search').on('input', function () {
        clearTimeout(brandSearchTimeout);
        const query = $(this).val();
        console.log('Header brand search query:', query);

        if (query.length < 2) {
            $('#header_brand_suggestions').hide().empty();
            return;
        }

        brandSearchTimeout = setTimeout(function () {
            $.ajax({
                url: '/api/search-brands',
                data: {q: query},
                success: function (brands) {
                    console.log('Brands found:', brands);
                    $('#header_brand_suggestions').empty();

                    if (brands.length > 0) {
                        brands.forEach(function (brand) {
                            $('#header_brand_suggestions').append(
                                `<div class="header-suggestion-item" data-brand-id="${brand.id}" data-brand-name="${brand.name}">
                                            ${brand.name}
                                        </div>`
                            );
                        });
                        $('#header_brand_suggestions').show();
                    } else {
                        $('#header_brand_suggestions').hide();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Brand search error:', error, xhr.responseText);
                }
            });
        }, 300);
    });

    // Handle Brand Selection
    $(document).on('click', '.header-suggestion-item[data-brand-id]', function () {
        const brandId = $(this).data('brand-id');
        const brandName = $(this).data('brand-name');

        $('#header_brand_search').val(brandName);
        $('#header_brand_id').val(brandId);
        $('#header_brand_suggestions').hide();

        // Load models for selected brand
        loadHeaderModels(brandId);
    });

    // Store all models for searching
    let allModels = [];

    // Load Models by Brand
    function loadHeaderModels(brandId) {
        $('#header_model_search').prop('disabled', true).val('Loading..');
        $('#header_model_id').val('');
        allModels = [];

        $.ajax({
            url: '/api/models-by-brand',
            data: {brand_id: brandId},
            success: function (models) {
                allModels = models;
                $('#header_model_search').val('').prop('disabled', false).attr('placeholder', 'Model');

                if (models.length === 0) {
                    $('#header_model_search').attr('placeholder', 'No models found').prop('disabled', true);
                }
            }
        });
    }

    // Model Search with Autocomplete
    let modelSearchTimeout;
    $('#header_model_search').on('input', function () {
        clearTimeout(modelSearchTimeout);
        const query = $(this).val().toLowerCase();

        if (query.length === 0) {
            $('#header_model_suggestions').hide().empty();
            $('#header_model_id').val('');
            return;
        }

        modelSearchTimeout = setTimeout(function () {
            const filteredModels = allModels.filter(model =>
                model.name.toLowerCase().includes(query)
            );

            $('#header_model_suggestions').empty();

            if (filteredModels.length > 0) {
                filteredModels.forEach(function (model) {
                    $('#header_model_suggestions').append(
                        `<div class="header-suggestion-item" data-model-id="${model.id}" data-model-name="${model.name}">
                                    ${model.name}
                                </div>`
                    );
                });
                $('#header_model_suggestions').show();
            } else {
                $('#header_model_suggestions').hide();
            }
        }, 200);
    });

    // Handle Model Selection
    $(document).on('click', '.header-suggestion-item[data-model-id]', function () {
        const modelId = $(this).data('model-id');
        const modelName = $(this).data('model-name');

        $('#header_model_search').val(modelName);
        $('#header_model_id').val(modelId);
        $('#header_model_suggestions').hide();
    });

    // Also update model ID when user clicks away from suggestions but has typed a matching model
    $('#header_model_search').on('blur', function() {
        const typedText = $(this).val().trim().toLowerCase();
        if (typedText && allModels.length > 0) {
            const exactMatch = allModels.find(model => model.name.toLowerCase() === typedText);
            if (exactMatch) {
                $('#header_model_id').val(exactMatch.id);
            }
        }
    });

    // Auto Part Keyword Search (optional suggestions, user can type freely)
    $('#header_part_search').on('input', function () {
        clearTimeout(partSearchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#header_part_suggestions').hide().empty();
            $('#header_part_id').val(''); // Clear part ID when input changes
            return;
        }

        // Show suggestions but don't require selection
        partSearchTimeout = setTimeout(function () {
            $.ajax({
                url: '/api/search-parts',
                data: {
                    q: query,
                    lang: 'az'
                },
                success: function (parts) {
                    $('#header_part_suggestions').empty();

                    if (parts.length > 0) {
                        parts.forEach(function (part) {
                            $('#header_part_suggestions').append(
                                `<div class="header-suggestion-item" data-part-id="${part.id}" data-part-name="${part.name}">
                                            <strong>${part.name}</strong>
                                            ${part.description ? '<br><small class="text-muted">' + part.description + '</small>' : ''}
                                        </div>`
                            );
                        });
                        $('#header_part_suggestions').show();
                    } else {
                        $('#header_part_suggestions').hide();
                    }
                }
            });
        }, 300);
    });

    // Handle Part Selection (optional - user can also just type and not select)
    $(document).on('click', '.header-suggestion-item[data-part-id]', function () {
        const partId = $(this).data('part-id');
        const partName = $(this).data('part-name');
        $('#header_part_search').val(partName);
        $('#header_part_id').val(partId);
        $('#header_part_suggestions').hide();
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#header_brand_search, #header_brand_suggestions').length) {
            $('#header_brand_suggestions').hide();
        }
        if (!$(e.target).closest('#header_model_search, #header_model_suggestions').length) {
            $('#header_model_suggestions').hide();
        }
        if (!$(e.target).closest('#header_part_search, #header_part_suggestions').length) {
            $('#header_part_suggestions').hide();
        }
    });

    // Search Button Handler
    $('#header_search_btn').on('click', function () {
        const brandText = $('#header_brand_search').val().trim();
        let brandId = $('#header_brand_id').val();
        const modelText = $('#header_model_search').val().trim();
        let modelId = $('#header_model_id').val();
        const partText = $('#header_part_search').val().trim();
        let partId = $('#header_part_id').val();

        // If brand text exists but no ID, try to find the brand ID
        if (brandText && !brandId) {
            // Search for exact brand match in real-time
            $.ajax({
                url: '/api/search-brands',
                data: { q: brandText },
                async: false, // Make synchronous to wait for result
                success: function(brands) {
                    if (brands.length > 0) {
                        // Try to find exact match first
                        const exactMatch = brands.find(b => b.name.toLowerCase() === brandText.toLowerCase());
                        brandId = exactMatch ? exactMatch.id : brands[0].id;
                        $('#header_brand_id').val(brandId);
                    }
                }
            });
        }

        // If model text exists but no ID, try to find in allModels array
        if (modelText && !modelId && allModels.length > 0) {
            const exactMatch = allModels.find(m => m.name.toLowerCase() === modelText.toLowerCase());
            if (exactMatch) {
                modelId = exactMatch.id;
                $('#header_model_id').val(modelId);
            }
        }

        // If part text exists but no ID, try to find the part ID
        if (partText && !partId) {
            $.ajax({
                url: '/api/search-parts',
                data: { q: partText, lang: 'az' },
                async: false,
                success: function(parts) {
                    if (parts.length > 0) {
                        const exactMatch = parts.find(p => p.name.toLowerCase() === partText.toLowerCase());
                        partId = exactMatch ? exactMatch.id : parts[0].id;
                        $('#header_part_id').val(partId);
                    }
                }
            });
        }

        // Build search URL with ONLY IDs (cleaner URLs)
        let searchParams = new URLSearchParams();

        // Add only IDs to URL
        if (brandId) {
            searchParams.append('brand_id', brandId);
        }
        if (modelId) {
            searchParams.append('auto_model_id', modelId);
        }
        if (partId) {
            searchParams.append('auto_part_id', partId);
        }

        // If only part name without brand/model/partId, add it as keyword
        if (partText && !brandId && !modelId && !partId) {
            searchParams.append('keyword', partText);
        }

        // Check if at least one parameter is provided
        if (brandId || modelId || partId || partText) {
            window.location.href = '/search?' + searchParams.toString();
        } else {
            alert('Please enter at least one search term');
        }
    });

    // Enter key support
    $('#header_brand_search, #header_part_search, #header_model_search').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#header_search_btn').click();
        }
    });

    // Fill inputs from URL parameters on page load
    const urlParams = new URLSearchParams(window.location.search);
    const brandIdParam = urlParams.get('brand_id');
    const modelIdParam = urlParams.get('auto_model_id') || urlParams.get('model_id');
    const partIdParam = urlParams.get('auto_part_id') || urlParams.get('part_id');

    console.log('URL Parameters:', {
        brandId: brandIdParam,
        modelId: modelIdParam,
        partId: partIdParam
    });

    // Load brand name from ID
    if (brandIdParam) {
        console.log('Loading brand with ID:', brandIdParam);
        $('#header_brand_id').val(brandIdParam);
        $.ajax({
            url: '/api/search-brands',
            data: {id: brandIdParam},
            success: function (brands) {
                console.log('Brand loaded:', brands);
                if (brands.length > 0) {
                    const brand = brands[0];
                    $('#header_brand_search').val(brand.name);
                    $('#header_model_search').prop('disabled', false);
                    console.log('Brand input filled with:', brand.name);

                    // Load models for this brand
                    loadHeaderModels(brandIdParam);

                    // If model ID exists, set it after models load
                    if (modelIdParam) {
                        setTimeout(function() {
                            $('#header_model_id').val(modelIdParam);
                            // Find model name from loaded models
                            const model = allModels.find(m => m.id == modelIdParam);
                            if (model) {
                                $('#header_model_search').val(model.name);
                                console.log('Model input filled with:', model.name);
                            }
                        }, 500);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Brand load error:', error);
            }
        });
    }

    // Load part name from ID
    if (partIdParam) {
        console.log('Loading part with ID:', partIdParam);
        $('#header_part_id').val(partIdParam);
        $.ajax({
            url: '/api/search-parts',
            data: {id: partIdParam, lang: 'az'},
            success: function (parts) {
                console.log('Part loaded:', parts);
                if (parts.length > 0) {
                    const part = parts[0];
                    $('#header_part_search').val(part.name);
                    console.log('Part input filled with:', part.name);
                }
            },
            error: function(xhr, status, error) {
                console.error('Part load error:', error);
            }
        });
    }

    // ========================================
    // AUTO PARTS SEARCH FORM FUNCTIONALITY
    // Version: 2.0.0
    // ========================================

    // Brand Autocomplete for Auto Parts Form
    let autoPartsBrandTimeout;
    $('#brand_search').on('input', function() {
        clearTimeout(autoPartsBrandTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#brand_suggestions').hide().empty();
            return;
        }

        autoPartsBrandTimeout = setTimeout(function() {
            $.ajax({
                url: '/api/search-brands',
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

    // Handle Brand Selection for Auto Parts Form
    $(document).on('click', '.suggestion-item[data-brand-id]', function() {
        const brandId = $(this).data('brand-id');
        const brandName = $(this).data('brand-name');

        $('#brand_search').val(brandName);
        $('#brand_id').val(brandId);
        $('#brand_suggestions').hide();

        // Load models for selected brand
        loadModels(brandId);
    });

    // Load Models by Brand for Auto Parts Form
    function loadModels(brandId) {
        $('#model_select').prop('disabled', true).html('<option value="">Loading...</option>');

        $.ajax({
            url: '/api/models-by-brand',
            data: { brand_id: brandId },
            success: function(models) {
                $('#model_select').empty().append('<option value="">Select model...</option>');

                if (models.length > 0) {
                    models.forEach(function(model) {
                        $('#model_select').append(`<option value="${model.id}" data-model-name="${model.name}">${model.name}</option>`);
                    });
                    $('#model_select').prop('disabled', false);
                } else {
                    $('#model_select').append('<option value="">No models found</option>');
                }
            }
        });
    }

    // Handle Model Selection - Update hidden field with model name
    $('#model_select').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const modelName = selectedOption.data('model-name') || selectedOption.text();
        $('#model_name').val(modelName);
    });

    // Auto Part Autocomplete for Auto Parts Form
    $('#part_search').on('input', function() {
        clearTimeout(partSearchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#part_suggestions').hide().empty();
            return;
        }

        partSearchTimeout = setTimeout(function() {
            $.ajax({
                url: '/api/search-parts',
                data: {
                    q: query,
                    lang: $('html').attr('lang') || 'az'
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

    // Handle Part Selection for Auto Parts Form
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

    // Reset Form for Auto Parts Search
    $('#resetSearchBtn').on('click', function() {
        $('#autoPartsSearchForm')[0].reset();
        $('#brand_id, #part_id, #model_name').val('');
        $('#model_select').prop('disabled', true).html('<option value="">First select a brand...</option>');
        $('#brand_suggestions, #part_suggestions').hide().empty();
    });

    // Form Validation for Auto Parts Search
    $('#autoPartsSearchForm').on('submit', function(e) {
        if (!$('#brand_id').val() && !$('#model_select').val() && !$('#part_id').val()) {
            e.preventDefault();
            alert('Please select at least one search criteria');
            return false;
        }
    });
});

function show_order_details(order_id) {
    $('#order-details-modal-body').html(null);

    if (!$('#modal-size').hasClass('modal-lg')) {
        $('#modal-size').addClass('modal-lg');
    }

    $.post('/admin/orders/details', {
        _token: AIZ.data.csrf,
        order_id: order_id
    }, function (data) {
        $('#order-details-modal-body').html(data);
        $('#order_details').modal();
        $('.c-preloader').hide();
        AIZ.plugins.bootstrapSelect('refresh');
    });
}
