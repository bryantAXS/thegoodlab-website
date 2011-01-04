$(function(){
	if ($().mxgoogleMaps) {} else {
		$.fn.mxgoogleMaps = function (options) {
			var opts = $.extend({}, $.mxgoogleMaps.defaults, options);
			return this.each(function () {
				$.mxgoogleMaps.gMap[opts.field_id] = new google.maps.Map(this, opts);
				google.maps.event.trigger($.mxgoogleMaps.gMap[opts.field_id], 'resize');
				$.mxgoogleMaps.geocoder = new google.maps.Geocoder();
				$.mxgoogleMaps.mapsConfiguration(opts);
				if (opts.cp) {
					$.mxgoogleMaps.cp_ini(opts)
				}
			})
		}
	}
});

$.mxgoogleMaps = {
    cp_ini: function (opts) {
        google.maps.event.addListener($.mxgoogleMaps.gMap[opts.field_id], "dragend", function () {
            $.mxgoogleMaps.set_field_data(opts.field_id)
        });
        google.maps.event.addListener($.mxgoogleMaps.gMap[opts.field_id], 'zoom_changed', function () {
            $.mxgoogleMaps.set_field_data(opts.field_id)
        });
        $("." + opts.field_id + "_btn_geocode").click(function () {
            $.mxgoogleMaps.geocode_address(opts.field_id)
        });
        $("." + opts.field_id + "_btn_addmarker").click(function () {
            $.mxgoogleMaps.addmarker($.mxgoogleMaps.gMap[opts.field_id].getCenter(), opts.field_id, opts.max_points, true, false, opts.custom_fields)
        });
        $("." + opts.field_id + "_btn_cmarker").click(function () {
            $.mxgoogleMaps.marker[opts.field_id].setPosition($.mxgoogleMaps.gMap[opts.field_id].getCenter())
        });
        $("#hold_" + (opts.field_id).replace("_id_", "_") + " .hide_field span").click(function () {
            google.maps.event.trigger($.mxgoogleMaps.gMap[opts.field_id], 'resize')
        });
        $("." + opts.field_id + "_btn_apply").click(function () {
            var _field_id = $(this).attr('rel');
            var _marker = $.mxgoogleMaps.marker[$.mxgoogleMaps.activePoint[_field_id]];
            var _icon_name = $("#gmap-icon_" + _field_id).val();
            _marker.icon_name = _icon_name;
            _marker.setIcon(marker_icons_path + _icon_name);
            for (var g_id in opts.custom_fields) {
                $('input[name="' + _field_id + '[' + _marker.id + '][' + opts.custom_fields[g_id].f_name + ']"]').val($('#' + opts.custom_fields[g_id].f_name + '_' + _field_id).val())
            };
            $('input[name="' + _field_id + '[' + _marker.id + '][icon]"]').val($('#gmap-icon_' + _field_id).val())
        });
        $("." + opts.field_id + "_btn_move").click(function () {
            $.mxgoogleMaps.marker[$.mxgoogleMaps.activePoint[$(this).attr('rel')]].setPosition($.mxgoogleMaps.gMap[opts.field_id].getCenter())
        });
        $("." + opts.field_id + "_btn_delete").click(function () {
            $.mxgoogleMaps.max_points[$(this).attr('rel')] = $.mxgoogleMaps.max_points[$(this).attr('rel')] - 1;
            $.mxgoogleMaps.marker[$.mxgoogleMaps.activePoint[$(this).attr('rel')]].setMap(null);
            $("#m" + $.mxgoogleMaps.activePoint[$(this).attr('rel')]).remove()
        })
    },
    mapsConfiguration: function (opts) {
        var center = $.mxgoogleMaps.mapLatLong(opts.latitude, opts.longitude);
        $.mxgoogleMaps.gMap[opts.field_id].setCenter(center, opts.zoom);
        if (opts.markers) {
            $.mxgoogleMaps.max_points[opts.field_id] = 0;
            $.mxgoogleMaps.icon[opts.field_id] = opts.icon;
            $.mxgoogleMaps.mapMarkers(center, opts.markers, opts.field_id, opts.max_points, opts.cp, opts.custom_fields)
        }
        if (opts.overlays) {
            $.mxgoogleMaps.addOverlay(center, opts.overlays, center, markers, opts.cp)
        }
        if (opts.custom_fields) {
            field_list = "";
            for (var g_id in opts.custom_fields) {
                field_list = field_list + '<label  for="' + opts.custom_fields[g_id].f_name + '">' + opts.custom_fields[g_id].label + '</label>';
                field_list = field_list + '<input autocomplete="off" spellcheck="false" id="' + opts.custom_fields[g_id].f_name + '_' + opts.field_id + '" name="' + opts.custom_fields[g_id].f_name + '" type="text">'
            };
            $('#panel_main_el_' + opts.field_id + ' .custom_fields').html(field_list)
        }
    },
    directions: {},
    latitude: '',
    longitude: '',
    latlong: {},
    maps: {},
    marker: {},
    markers: {},
    max_points: {},
    infowindow: {},
    imageBounds: {},
    overlays: {},
    overlay: {},
    gMap: {},
    activePoint: {},
    geocoder: {},
    cp: false,
    field_id: '',
    icon: {},
    mapLatLong: function (latitude, longitude) {
        return new google.maps.LatLng(latitude, longitude)
    },
    mapMarkers: function (center, markers, field_id, max_points, cp, custom_fields) {
        if (typeof(markers.length) == 'undefined') markers = [markers];
        i = 0;
        j = 0;
        for (var g_id in markers) {
            $.mxgoogleMaps.addmarker(center, field_id, max_points, cp, markers[g_id], custom_fields)
        }
    },
    updateMarkerPosition: function (latLng, field_id, zoom) {},
    addOverlay: function (center, field_id, max_points, cp, opts) {},
    addmarker: function (center, field_id, max_points, cp, opts, custom_fields) {
        if ($.mxgoogleMaps.max_points[field_id] < max_points || !cp) {
            if (opts) {
                id = opts.marker_id;
                $.mxgoogleMaps.marker[id] = new google.maps.Marker({
                    position: new google.maps.LatLng(opts.latitude, opts.longitude),
                    map: $.mxgoogleMaps.gMap[field_id],
                    id: id,
                    icon_name: opts.icon,
                    opts: opts
                });
                if (typeof(opts.infow) != 'undefined') {
                    $.mxgoogleMaps.infowindow[id] = new google.maps.InfoWindow({
                        content: opts.infow
                    });
                    google.maps.event.addListener($.mxgoogleMaps.marker[id], 'click', function () {
                        $.mxgoogleMaps.infowindow[this.id].open(this.map, this)
                    })
                };
                if (cp) {
                    $.mxgoogleMaps.add_hidden_fields(center, field_id, id, custom_fields);
                    for (var g_id in custom_fields) {
                        $('input[name="' + field_id + '[' + id + '][' + custom_fields[g_id].f_name + ']"]').val(opts[custom_fields[g_id].f_name])
                    };
                    $('input[name="' + field_id + '[' + id + '][icon]"]').val(opts.icon);
                    $('input[name="' + field_id + '[' + id + '][lat]"]').val(opts.latitude);
                    $('input[name="' + field_id + '[' + id + '][long]"]').val(opts.longitude)
                }
                if (opts.icon != 'default' && opts.icon != '' ) {
                    $.mxgoogleMaps.marker[id].setIcon(marker_icons_path + opts.icon)
                }
            } else {
                id = Math.floor(Math.random() * 1000 + 1);
                $.mxgoogleMaps.marker[id] = new google.maps.Marker({
                    position: center,
                    map: $.mxgoogleMaps.gMap[field_id],
                    id: id,
                    icon_name: $.mxgoogleMaps.icon[field_id]
                });
                if (cp) {
                    $.mxgoogleMaps.add_hidden_fields(center, field_id, id, custom_fields);
                    $.mxgoogleMaps.set_active_marker($.mxgoogleMaps.marker[id], field_id, custom_fields);
                    if ($.mxgoogleMaps.icon[field_id] != 'default' && $.mxgoogleMaps.icon[field_id] != '') {
                        $.mxgoogleMaps.marker[id].setIcon(marker_icons_path + $.mxgoogleMaps.icon[field_id])
                    }
                }
            }
            if (cp) {
                $.mxgoogleMaps.marker[id].draggable = true;
                google.maps.event.addListener($.mxgoogleMaps.marker[id], "dragend", function () {
                    $.mxgoogleMaps.set_active_marker(this, field_id, custom_fields);
                    $.mxgoogleMaps.set_input(this, field_id, custom_fields)
                });
                google.maps.event.addListener($.mxgoogleMaps.marker[id], 'click', function () {
                    $.mxgoogleMaps.set_active_marker(this, field_id, custom_fields);
                    $.mxgoogleMaps.set_input(this, field_id, custom_fields);
                    $.mxgoogleMaps.gMap[this.map.field_id].setCenter(this.getPosition());
                    $.mxgoogleMaps.set_field_data(field_id)
                })
            }
            $.mxgoogleMaps.max_points[field_id] += 1
        }
    },
    set_active_marker: function (marker, field_id, custom_fields) {
        $.mxgoogleMaps.updateMarkerPosition(marker.getPosition(), field_id, $.mxgoogleMaps.gMap[field_id].getZoom());
        $.mxgoogleMaps.geocodePosition(marker.getPosition(), field_id, custom_fields) ;
        $.mxgoogleMaps.activePoint[marker.map.field_id] = marker.id;
        $.mxgoogleMaps.set_input(marker, field_id, custom_fields)
    },
    add_hidden_fields: function (center, field_id, point_id, custom_fields) {
        fields = "";
        for (var g_id in custom_fields) {
            fields = fields + '<input name="' + field_id + '[' + point_id + '][' + custom_fields[g_id].f_name + ']"  type="hidden" "/>'
        };
        fields = '<div id="m' + point_id + '"><input name="' + field_id + '[order][]"  value="' + point_id + '" type="hidden"/>' + fields + '<input name="' + field_id + '[' + point_id + '][lat]"  value="' + center.lat() + '"type="hidden"/><input name="' + field_id + '[' + point_id + '][long]" value="' + center.lng() + '" type="hidden"/><input name="' + field_id + '[' + point_id + '][icon]" type="hidden"/></div>';
        $("#" + field_id + "_data").append(fields)
    },
    set_field_data: function (field_id) {
        center = $.mxgoogleMaps.gMap[field_id].getCenter();
        var data = center.lat() + "|" + center.lng() + '|' + $.mxgoogleMaps.gMap[field_id].getZoom();
        $('input[name="' + field_id + '[field_data]"]').val(data)
    },
    set_input: function (marker, field_id, custom_fields) {
        _icon_name = marker.icon_name;
        center = marker.getPosition();
        for (var g_id in custom_fields) {
            $('#' + custom_fields[g_id].f_name + '_' + field_id).val($('input[name="' + field_id + '[' + marker.id + '][' + custom_fields[g_id].f_name + ']"]').val())
        };
        $('input[name="' + field_id + '[' + marker.id + '][lat]"]').val(center.lat());
        $('input[name="' + field_id + '[' + marker.id + '][long]"]').val(center.lng());
        $('#latitude_' + field_id).val(center.lat());
        $('#longitude_' + field_id).val(center.lng());
        $("#gmap-icon_" + field_id).val(_icon_name)
    },
    geocode_address: function (field_id) {
        var address = document.getElementById(field_id + "_address").value;
        $.mxgoogleMaps.geocoder.geocode({
            "address": address,
            "partialmatch": true
        }, function (results, status) {
            if (status == "OK" && results.length > 0) {
                $.mxgoogleMaps.gMap[field_id].fitBounds(results[0].geometry.viewport);
                $.mxgoogleMaps.set_field_data(field_id);
                $.mxgoogleMaps.updateMarkerPosition(results[0].geometry.location, field_id, $.mxgoogleMaps.gMap[field_id].getZoom());
            } else {
                alert("Geocode was not successful for the following reason: " + status)
            }
        })
    },
			//	var clean_vr = new RegExp("/{(.*?)}/", "g");
    geocodePosition: function (pos, field_id, custom_fields) {
        $.mxgoogleMaps.geocoder.geocode({
            latLng: pos
        }, function (responses) {
            if (responses && responses.length > 0) {
                $("#" + field_id + "_address").val(responses[0].formatted_address);
				vvvv  = "";
				myAddress = [];
				for (i = 0; i < responses[0].address_components.length; i++) {
					for (j = 0; j< responses[0].address_components[i].types.length; j++) {
						myAddress[responses[0].address_components[i].types[j] + "_ln"] = responses[0].address_components[i].long_name ;
						myAddress[responses[0].address_components[i].types[j] + "_sn"] = responses[0].address_components[i].short_name;			
					}
				}
		
				var clean_vr = new RegExp(/{(.*?)}/);
				
				for (var g_id in custom_fields) {
					var field_val = custom_fields[g_id].pattern;
					
					for (patt in myAddress)
					{
						field_val = field_val.replace("{" + patt + "}", myAddress[patt]);
					};
					field_val = field_val.replace(clean_vr, "");
					
					$('#' + custom_fields[g_id].f_name + '_' + field_id).val(field_val);						
					
				}	
				//alert (myAddress["country_long_name"]);
						
				
            } else {
                alert("Cannot determine address at this location.")
            }
        })
    },
    defaults: {
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        zoom: 8,
        draggable: true,
        navigationControl: true,
        scaleControl: true,
        mapTypeControl: true,
        scrollwheel: true,
        max_points: 1,
        cp: false,
        icon: 'default'
    }
};