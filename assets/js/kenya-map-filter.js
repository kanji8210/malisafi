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

  function getCountyNameFromTarget($target, mapData) {
    let county = $target.data('county');
    const subcounty = $target.data('subcounty');

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

    if (!county && subcounty) {
      county = findCountyForSubcounty(subcounty, mapData);
    }

    return county || '';
  }

  function findCountyKey(name, counts) {
    const target = normalize(name);
    let found = '';
    Object.keys(counts || {}).some(function(key) {
      if (normalize(key) === target) {
        found = key;
        return true;
      }
      return false;
    });
    return found || name;
  }

  function attachSvgHandlers($container, mapData) {
    $container.off('click', '[data-county], [data-subcounty], [title], path');
    $container.on('click', '[data-county], [data-subcounty], [title], path', function() {
      const $target = $(this);
      let county = getCountyNameFromTarget($target, mapData);
      const subcounty = $target.data('subcounty');
      const $form = $container.closest('.malisafi-kenya-map-filter').find('form');
      const $countySelect = $form.find('[name="county"]');
      const $subcountySelect = $form.find('[name="subcounty"]');

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

  function attachTooltipHandlers($container, mapData, counts) {
    const $tooltip = $('<div class="malisafi-kenya-map-tooltip" aria-hidden="true"></div>');
    if (!$container.find('.malisafi-kenya-map-tooltip').length) {
      $container.append($tooltip);
    }

    $container
      .off('mouseenter mousemove', '[data-county], [data-subcounty], [title], path')
      .on('mouseenter mousemove', '[data-county], [data-subcounty], [title], path', function(event) {
        const $target = $(this);
        const countyName = getCountyNameFromTarget($target, mapData);
        if (!countyName) {
          return;
        }

        const key = findCountyKey(countyName, counts || {});
        const stats = (counts && counts[key]) ? counts[key] : { rent: 0, sale: 0 };
        const rentCount = typeof stats.rent !== 'undefined' ? stats.rent : 0;
        const saleCount = typeof stats.sale !== 'undefined' ? stats.sale : 0;

        $tooltip.html(
          '<strong>' + countyName + ' county</strong><br>' +
          'For rent available (' + rentCount + ')<br>' +
          'For sale available (' + saleCount + ')'
        );

        const offset = $container.offset();
        const left = event.pageX - offset.left + 12;
        const top = event.pageY - offset.top + 12;
        $tooltip.css({ left: left + 'px', top: top + 'px' }).addClass('is-visible');
      })
      .off('mouseleave', '[data-county], [data-subcounty], [title], path')
      .on('mouseleave', '[data-county], [data-subcounty], [title], path', function() {
        $container.find('.malisafi-kenya-map-tooltip').removeClass('is-visible');
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
    const countyCounts = malisafiKenyaMapFilter.counts || {};
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
        attachTooltipHandlers($mapContainer, mapData, countyCounts);
      } else if (malisafiKenyaMapFilter.svgUrl) {
        $.get(malisafiKenyaMapFilter.svgUrl)
          .done(function(svg) {
            $mapContainer.html(svg);
            attachSvgHandlers($mapContainer, mapData);
            attachTooltipHandlers($mapContainer, mapData, countyCounts);
          })
          .fail(function() {
            attachSvgHandlers($mapContainer, mapData);
            attachTooltipHandlers($mapContainer, mapData, countyCounts);
          });
      } else {
        attachSvgHandlers($mapContainer, mapData);
        attachTooltipHandlers($mapContainer, mapData, countyCounts);
      }

      const initialCounty = $countySelect.val();
      if (initialCounty) {
        populateSubcounties($subcountySelect, initialCounty, mapData, $subcountySelect.val());
        highlightCounty($mapContainer, initialCounty);
      }
    });
  });
})(jQuery);
