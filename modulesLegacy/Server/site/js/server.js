/**
 * Server
 */
(function (exports, $) {
	var
	Server = exports.Server = function () {
		this.pulse_handlers = {};
	},
	server = new Server();
	
	Server.add_pulse_handler = function (name, func) {
		server.pulse_handlers[name] = func;
	}
})(window, window.jQuery);