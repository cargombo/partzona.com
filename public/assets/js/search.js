// Header Search Functionality
$(document).ready(function () {
    console.log('Header search script loaded');
    let brandSearchTimeout;
    let partSearchTimeout;

    // Brand Autocomplete
    $('#header_brand_search').on('input', function () {
        clearTimeout(brandSearchTimeout);
        const query = $(this).val();
        console.log('Brand search query:', query);

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

    // Auto Part Keyword Search (optional suggestions, user can type freely)
    $('#header_part_search').on('input', function () {
        clearTimeout(partSearchTimeout);
        const query = $(this).val();

        if (query.length < 2) {
            $('#header_part_suggestions').hide().empty();
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
                                `<div class="header-suggestion-item" data-part-name="${part.name}">
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
    $(document).on('click', '.header-suggestion-item[data-part-name]', function () {
        const partName = $(this).data('part-name');
        $('#header_part_search').val(partName);
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
        const modelText = $('#header_model_search').val().trim();
        const partText = $('#header_part_search').val().trim();

        // Build search URL with separate parameters
        let searchParams = new URLSearchParams();

        if (brandText) {
            searchParams.append('brand', brandText);
        }
        if (modelText) {
            searchParams.append('model', modelText);
        }
        if (partText) {
            searchParams.append('part', partText);
        }

        // Check if at least one parameter is provided
        if (brandText || modelText || partText) {
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
    const brandParam = urlParams.get('brand');
    const modelParam = urlParams.get('model');
    const partParam = urlParams.get('part');

    if (brandParam) {
        $('#header_brand_search').val(brandParam);
        // Enable model input if brand is present
        $('#header_model_search').prop('disabled', false);

        // Search for the brand ID to load models
        $.ajax({
            url: '/api/search-brands',
            data: {q: brandParam},
            success: function (brands) {
                if (brands.length > 0) {
                    // Find exact match
                    const brand = brands.find(b => b.name === brandParam) || brands[0];
                    $('#header_brand_id').val(brand.id);
                    // Load models for this brand
                    loadHeaderModels(brand.id);

                    // Set model value after models are loaded
                    if (modelParam) {
                        setTimeout(function () {
                            $('#header_model_search').val(modelParam);
                        }, 500);
                    }
                }
            }
        });
    }
    if (modelParam && !brandParam) {
        $('#header_model_search').val(modelParam);
    }
    if (partParam) {
        $('#header_part_search').val(partParam);
    }
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
