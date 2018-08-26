// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains an AMD/jQuery module to expand and collapse categories.
 *
 * @package    block_courses_by_role
 * @copyright  2018 tim@avide.com.au
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function(factory) {
    if (typeof define === "function" && define.amd) {
        require.config({
            waitSeconds: 30
        });
        require(["jquery", "jqueryui"], factory);
    } else {
        /* eslint-env jquery */
        factory(jQuery);
    }
}(function($) {

    $(".cbr_expando").each(function(index,obj) {
        $(obj).on('click', '.cbr_toggle', function(a) {
            a.preventDefault();
            var $this = $(this),
                ul = $this.closest("li").find("ul"),
                i = $this.find("i");
            if (ul.hasClass("hidden")) {
                i.removeClass("fa-plus-square").addClass("fa-minus-square");
                ul.removeClass("hidden");
            } else {
                i.addClass("fa-plus-square").removeClass("fa-minus-square");
                ul.addClass("hidden");
            }
        });
    });

}));