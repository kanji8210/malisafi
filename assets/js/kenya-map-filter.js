(function($) {
  'use strict';

  function normalize(value) {
    return (value || '').toString().trim().toLowerCase();
  }

  function buildSubcountyMap(data) {
    const map = {};
    if (!data) {
      return map;
    }

    Object.keys(data).forEach(function(county) {
      const list = data[county] || [];
      map[county] = list.map(function(item) {
        if (typeof item === 'string') {
          return item;
        }
        if (item && item.name) {
          return item.name;
        }
        return '';
      }).filter(Boolean);
    });

    return map;
  }

  function findCountyForSubcounty(subcounty, map) {
    const target = normalize(subcounty);
    let found = '';
    Object.keys(map).some(function(county) {
      const match = map[county].some(function(name) {
        return normalize(name) === target;
      });
      if (match) {
        found = county;
      }
      return match;
    });
    return found;
  }

  function populateSubcounties($subcounty, county, map, selected) {
    const items = map[county] || [];
    $subcounty.empty();
    $subcounty.append($('<option>').val('').text('Select Sub-county...'));

    items.forEach(function(name) {
      const $opt = $('<option>').val(name).text(name);
      if (selected && normalize(selected) === normalize(name)) {
        $opt.prop('selected', true);
      }
      $subcounty.append($opt);
    });
  }

  function attachSvgHandlers($container, mapData) {
    $container.off('click', '[data-county], [data-subcounty], [title], path');
    $container.on('click', '[data-county], [data-subcounty], [title], path', function() {
      const $target = $(this);
      let county = $target.data('county');
      const subcounty = $target.data('subcounty');
      const $form = $container.closest('.malisafi-kenya-map-filter').find('form');
      const $countySelect = $form.find('[name="county"]');
      const $subcountySelect = $form.find('[name="subcounty"]');

      if (!county) {
        const titleAttr = $target.attr('title');
        if (titleAttr) {
          county = titleAttr;
        } else {
          const $title = $target.find('title');
          if ($title.length) {
            county = $title.text();
          }
        }
      }

      if (subcounty) {
        let resolvedCounty = county || findCountyForSubcounty(subcounty, mapData);
        if (resolvedCounty) {
          $countySelect.val(resolvedCounty).trigger('change');
          populateSubcounties($subcountySelect, resolvedCounty, mapData, subcounty);
          $subcountySelect.val(subcounty);
        }
      } else if (county) {
        $countySelect.val(county).trigger('change');
        populateSubcounties($subcountySelect, county, mapData, '');
      }

      $container.find('.is-selected').removeClass('is-selected');
      $target.addClass('is-selected');
    });
  }

  function highlightCounty($mapContainer, countyName) {
    $mapContainer.find('.is-selected').removeClass('is-selected');
    if (!countyName) {
      return;
    }

    const target = normalize(countyName);
    const $match = $mapContainer
      .find('[data-county], [title], path')
      .filter(function() {
        const $el = $(this);
        let name = $el.data('county');
        if (!name) {
          const titleAttr = $el.attr('title');
          if (titleAttr) {
            name = titleAttr;
          } else {
            const $title = $el.find('title');
            if ($title.length) {
              name = $title.text();
            }
          }
        }
        return normalize(name) === target;
      })
      .first();

    if ($match.length) {
      $match.addClass('is-selected');
    }
  }

  $(function() {
    if (typeof malisafiKenyaMapFilter === 'undefined') {
      return;
    }

    const mapData = buildSubcountyMap(malisafiKenyaMapFilter.subcounties || {});
    const $widget = $('.malisafi-kenya-map-filter');

    $widget.each(function() {
      const $wrap = $(this);
      const $countySelect = $wrap.find('[name="county"]');
      const $subcountySelect = $wrap.find('[name="subcounty"]');
      const $mapContainer = $wrap.find('.malisafi-kenya-map');

      $countySelect.on('change', function() {
        const county = $(this).val();
        populateSubcounties($subcountySelect, county, mapData, '');
        highlightCounty($mapContainer, county);
      });

      $wrap.find('.reset-btn').on('click', function() {
        $countySelect.val('');
        populateSubcounties($subcountySelect, '', mapData, '');
        $wrap.find('input[type="number"]').val('');
        $wrap.find('select[name="status"]').val('');
        highlightCounty($mapContainer, '');
      });

      if ($mapContainer.find('svg').length) {
        attachSvgHandlers($mapContainer, mapData);
      } else if (malisafiKenyaMapFilter.svgUrl) {
        $.get(malisafiKenyaMapFilter.svgUrl)
          .done(function(svg) {
            $mapContainer.html(svg);
            attachSvgHandlers($mapContainer, mapData);
          })
          .fail(function() {
            attachSvgHandlers($mapContainer, mapData);
          });
      } else {
        attachSvgHandlers($mapContainer, mapData);
      }

      const initialCounty = $countySelect.val();
      if (initialCounty) {
        populateSubcounties($subcountySelect, initialCounty, mapData, $subcountySelect.val());
        highlightCounty($mapContainer, initialCounty);
      }
    });
  });
})(jQuery);
