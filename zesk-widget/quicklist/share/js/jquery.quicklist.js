/*
 * QuickList list sorter and searcher
 */
(function(exports, $) {
    "use strict";
    var _ = exports._, zesk = exports.zesk, plugin_name = 'quicklist';

    var defaults = {
        arrange_widget: null,
        search_widget: null,
        search_fields: [],
        items: [],
        empty_content: "",
        search_empty_content: "",
        order_by: null
    };
    var QuickList = function(element, options) {
        var self = this, $this = $(element);

        this.$element = $this;
        if (this.$element.length === 0) {
            throw plugin_name + " unable to find primary list element " + element;
        }
        $.each(defaults, function(key) {
            var name = 'modal' + key.toCamelCase(), value = $this.data(name);
            self[key] = value ? value : this;
        });
        $.extend(this, options);
        this.init();
        this.render();
    };

    $.extend(QuickList.prototype, {
        init: function() {
            var self = this;

            this.is_original_list = true;
            this.search_phrase = null;
            this.render_search_phrase = null;
            this.order_by = null;
            this.render_order_by = null;
            this.dirty = true;
            this.render_items = [];

            if (this.arrange_widget) {
                this.$arrange_widget = $(this.arrange_widget);
                if (this.$arrange_widget.length === 0) {
                    zesk.log(plugin_name + " arrange widget " + this.arrange_widget + " not found on page");
                    this.arrange_widget = null;
                } else {
                    this.$arrange_widget.on("change", function() {
                        self.render_if_needed();
                    });
                }
            }
            if (_.isArray(this.search_fields) && this.items.length > 0) {
                var item = this.items[0];
                this.search_fields = _.filter(this.search_fields, function(field) {
                    var result = _.has(item.object, field);
                    if (!result) {
                        zesk.log(plugin_name + " search field " + field + " not found in object fields: " + _.keys(item.object).join(", "));
                    }
                    return result;
                });
                if (this.search_fields.length > 0 && this.search_widget) {
                    this.$search_widget = $(this.search_widget);
                    if (this.$search_widget.length === 0) {
                        zesk.log(plugin_name + " search widget " + this.search_widget + " not found on page");
                        this.search_widget = null;
                    } else {
                        this.$search_widget.on("keyup", function() {
                            self.render_if_needed();
                        });
                    }
                } else {
                    this.search_widget = null;
                }
            } else {
                this.search_widget = null;
            }

        },
        render_if_needed: function() {
            this.update_widget_state();
            if (this.render_order_by !== this.order_by) {
                this.dirty = true;
            }
            if (this.render_search_phrase !== this.search_phrase) {
                this.dirty = true;
            }
            if (this.dirty) {
                this.render();
            }
        },
        render: function() {
            var self = this;
            var map = {
                "phrase": this.search_phrase
            };

            this.update_widget_state();
            this.$element.empty();
            this.clean();
            if (this.render_items.length === 0) {
                this.$element.append(this.is_original_list ? this.empty_content.map(map) : this.search_empty_content.map(map));
                $('a.clear-search', this.$element).on("click", function () {
                    self.$search_widget.val("");
                    self.render_if_needed();
                });
            } else {
                $.each(this.render_items, function() {
                    self.$element.append(this.content);
                });
            }

            this.render_order_by = this.order_by;
            this.render_search_phrase = this.search_phrase;
        },
        update_widget_state: function() {
            this.is_original_list = true;
            this.search_phrase = null;
            if (this.search_widget) {
                var q = $.trim(this.$search_widget.val());
                if (q !== "") {
                    this.search_phrase = q;
                    this.is_original_list = false;
                }
            }
            this.order_by = null;
            if (this.arrange_widget) {
                this.order_by = this.$arrange_widget.val();
            }
        },
        clean: function() {
            var self = this, items = null;
            if (!this.dirty) {
                return;
            }
            if (this.search_phrase) {
                var phrase = this.search_phrase.toLowerCase();
                items = _.filter(this.items, function(item) {
                    var matched = false;
                    _.each(self.search_fields, function(field) {
                        if (item.object[field].toLowerCase().indexOf(phrase) >= 0) {
                            matched = true;
                        }
                    });
                    return matched;
                });
            } else {
                items = this.items;
            }
            this.render_items = items.sort(function(a, b) {
                var a_field = String(a.object[self.order_by] || "").toLowerCase();
                var b_field = String(b.object[self.order_by] || "").toLowerCase();
                if (a_field < b_field) {
                    return -1;
                }
                if (a_field === b_field) {
                    return 0;
                }
                return 1;
            });
            this.dirty = false;

        }
    });
    $.fn[plugin_name] = function(options) {
        var element_apply = function() {
            var $this = $(this);
            $this.data(plugin_name, new QuickList($this, options));
        };
        $(this).each(function(index, item) {
            element_apply.call(item);
        });
    };
}(window, window.jQuery));
