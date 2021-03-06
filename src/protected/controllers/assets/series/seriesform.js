/* 
 * Copyright (C) 2014 rfgunion.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

function onchange_typeid() {
	var seriestypeid = $("#Series_typeid option:selected").val();
	var html = $("#seriestype_"+seriestypeid).html();
	if (html === null) {
		return;
	}
	var props = html.split("$$");
	// props:
	// 0 = typeid
	// 1 = name
	// 2 = defaultMethod
	// 3 = defaultParam1
	// 4 = defaultParam2
	var now = new Date();
	var name = props[1] + " " + (now.getYear() + 1900);
	if (name.substr(0, 6) === "Sunday") {
		name += "-"+(now.getYear()-109);
	}
	$("#Series_name").val(name);
	$("#Series_defaultMethod").val(props[2]).trigger('change');

}

function onchange_method() {
	var method = $("#Series_defaultMethod > option:selected").val();
	var param1 = "";
	var param2 = "";
	var foundit = false;
	$(".seriestype").each(function() {
		if (foundit) return;
		var html = $(this).html();
		var props = html.split("$$");
		// props:
		// 0 = typeid
		// 1 = name
		// 2 = defaultMethod
		// 3 = defaultParam1
		// 4 = defaultParam2
		if (method === props[2]) {
			param1 = props[3];
			param2 = props[4];
			foundit = true;
		}
	});
	var disableParams = (method === 'TOD' && param1 == "" && param2 == "");
	$("#Series_defaultParam1").prop("disabled", disableParams);
	$("#Series_defaultParam2").prop("disabled", disableParams);
	$("#Series_defaultParam1").val(param1);
	$("#Series_defaultParam2").val(param2);
}

$(function() {
	$("#Series_typeid").change(onchange_typeid);
	$("#Series_defaultMethod").change(onchange_method);
	onchange_typeid();
});
