// This file is part of Moodle - http://moodle.org///
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
 * Test service call.
 * @module     metadataextractor_tika/test_service
 * @copyright  2023 Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as Ajax from 'core/ajax';
import * as LoadingIcon from 'core/loadingicon';
import * as Notification from 'core/notification';
import * as Templates from 'core/templates';

/**
 * Check if the service is ready.
 *
 * @method isServiceReady
 * @return {boolean} If the service is ready
 */
const isServiceReady = () => {
    var request = {
        methodname: 'metadataextractor_tika_is_service_ready',
        args: {}
    };
    return Ajax.call([request])[0];
};

/**
 * Check if the service is ready.
 *
 * @method testService
 * @param {object} container the container to show the HTML
 * @param {string} serviceName the name of the service
 */
const testService = (container, serviceName) => {
    var isServiceReadyPromise = isServiceReady();
    LoadingIcon.addIconToContainer(container, isServiceReadyPromise);

    isServiceReadyPromise.then(function(ready) {
        var contentPromise = Templates.render('metadataextractor_tika/service_status', {
            ready: ready.ready,
            servicename: serviceName
        });

        contentPromise.then(function(html, js) {
            return Templates.replaceNodeContents(container, html, js);
        }).catch(Notification.exception);

        return isServiceReadyPromise;
    }).catch(Notification.exception);
};

/**
 * Init the event watcher.
 *
 * @param {string} containerId The ID of the container to place the HTML
 * @param {string} buttonId The ID of the button to watch for click events
 * @param {string} serviceName The service name
 */
export const init = (containerId, buttonId, serviceName) => {
    var button = $("#" + buttonId);
    var container = $("#" + containerId);

    button.on("click", function(event) {
        event.preventDefault();
        testService(container, serviceName);
    });
};
