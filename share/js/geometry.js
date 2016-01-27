(function (exports) {
	"use strict";
	var 
	Point2D,
	Bound2D,
	zesk = exports.zesk;
	
	function between(min, x, max) {
		return x >= min && x <= max;
	}
	function overlap(a0,a1,b0,b1) {
		return between(a0,b0,a1) || between(a0,b1,a1) || between(b0,a0,b1) || between(b0,a1,b1);
	}
	function is_numeric(x) {
		return typeof x === "number";
	}
	/*
	 * Point2D
	 */
	Point2D = exports.Point2D = function (x, y) {
		this.constructor.super.call(this);
		if (x instanceof Point2D) {
			this.x = x.x;
			this.y = x.y;
		} else {
			this.x = parseFloat(x) || 0.0;
			this.y = parseFloat(y) || 0.0;
		}
	};
	zesk.inherit(Point2D, exports.ZObject, {
		left_top: function (units) {
			units = units || "px";
			return { 
				left: parseInt(this.x, 10) + "px", 
				top: parseInt(this.y, 10) + "px" 
			};
		}
	});

	Point2D.prototype.clone = function () {
		var x = new Point2D(this.x, this.y);
		zesk.each(this, function (k) {
			x[k] = this;
		});
		return x;
	};
	Point2D.prototype.add = function (mixed, y) {
		if (mixed instanceof Point2D) {
			this.x += mixed.x;
			this.y += mixed.y;
		} else if (is_numeric(mixed) && is_numeric(y)) {
			this.x += mixed;
			this.y += y;
		}
		return this;
	};
	Point2D.prototype.zero = function (set) {
		if (!set) {
			return (this.x === 0 && this.y === 0);
		}
		this.x = this.y = 0;
		return this;
	};
	Point2D.prototype.subtract = function (mixed, y) {
		if (mixed instanceof Point2D) {
			this.x -= mixed.x;
			this.y -= mixed.y;
		} else if (is_numeric(mixed) && is_numeric(y)) {
			this.x -= mixed;
			this.y -= y;
		}
		return this;
	};
	Point2D.prototype.multiply = function (mixed, y) {
		if (mixed instanceof Point2D) {
			this.x *= mixed.x;
			this.y *= mixed.y;
		} else if (is_numeric(mixed) && is_numeric(y)) {
			this.x *= mixed;
			this.y *= y;
		}
		return this;
	};
	Point2D.prototype.lengthsq = function () {
		return this.x * this.x + this.y * this.y;
	};
	Point2D.prototype.length = function () {
		return Math.sqrt(this.lengthsq());
	};
	Point2D.prototype.divide = function (mixed, y) {
		if (mixed instanceof Point2D) {
			this.x /= mixed.x;
			this.y /= mixed.y;
		} else if (is_numeric(mixed) && is_numeric(y)) {
			this.x /= mixed;
			this.y /= y;
		}
		return this;
	};
	Point2D.prototype.abs = function () {
		this.x = Math.abs(this.x);
		this.y = Math.abs(this.y);
		return this;
	};
	
	/**
	 * Bound2D
	 */
	Bound2D = exports.Bound2D = function (a) {
		if (a instanceof Point2D) {
			this.min = a.clone();
			this.max = a.clone();
		} else {
			this.min = null;
			this.max = null;
		}
	};
	zesk.inherit(Bound2D, exports.ZObject);
	Bound2D.prototype.empty = function (set) {
		if (!set) {
			return this.min === null || this.max === null;
		}
		this.min = null;
		this.max = null;
		return this;
	};
	Bound2D.prototype.move = function (x, y) {
		if (x instanceof Point2D) {
			this.move(x.x, x.y);
		} else {
			this.min.add(x,y);
			this.max.add(x,y);
		}
		return this;
	};
	Bound2D.prototype.zero = function (set) {
		if (this.min === null) {
			return set ? this.expand(new Point2D(0,0)) : false;
		}
		if (!set) {
			return this.min.zero() && this.max.zero();
		}
		this.min.zero(true);
		this.max.zero(true);
		return this;
	};
	Bound2D.prototype.expand = function (mixed, y) {
		if (mixed instanceof Point2D) {
			if (this.empty()) {
				this.min = mixed.clone();
				this.max = mixed.clone();
			} else {
				this.min.x = Math.min(mixed.x,this.min.x);
				this.min.y = Math.min(mixed.y,this.min.y);
				this.max.x = Math.max(mixed.x,this.max.x);
				this.max.y = Math.max(mixed.y,this.max.y);
			}
		} else if (mixed instanceof Bound2D) {
			this.expand(mixed.min);
			this.expand(mixed.max);
		} else if (is_numeric(mixed) && is_numeric(y)) {
			this.min.x = Math.min(this.min.x, mixed);
			this.min.y = Math.min(this.min.y, y);
			this.max.x = Math.max(this.max.x, mixed);
			this.max.y = Math.max(this.max.y, y);
		}
		return this;
	};
	Bound2D.prototype.add = function (mixed, y) {
		if (this.empty()) {
			return this;
		}
		if (mixed instanceof Point2D) {
			this.min.add(mixed);
			this.max.add(mixed);
		} else if (mixed instanceof Bound2D) {
			this.min.add(mixed.min);
			this.max.add(mixed.max);
		} else if (is_numeric(mixed) && is_numeric(y)) {
			this.min.add(mixed, y);
			this.max.add(mixed, y);
		}
		return this;
	};
	Bound2D.prototype.dimensions = function () {
		if (this.empty()) {
			return null;
		}
		return new Point2D(this.width(), this.height());
	};
	Bound2D.prototype.width = function (set) {
		if (this.empty()) {
			return null;
		}
		if (is_numeric(set)) {
			this.max.x = this.min.x + set;
			return this;
		}
		return this.max.x - this.min.x;
	};
	Bound2D.prototype.height = function (set) {
		if (this.empty()) {
			return null;
		}
		if (is_numeric(set)) {
			this.max.y = this.min.y + set;
			return this;
		}
		return this.max.y - this.min.y;
	};
	Bound2D.prototype.center = function () {
		if (this.empty()) {
			return null;
		}
		return new Point2D((this.min.x + this.max.x) * 0.5, (this.min.y + this.max.y) * 0.5);
	};
	Bound2D.prototype.area = function () {
		return this.width() * this.height();
	};
	Bound2D.prototype.inside = function (x) {
		if (x instanceof Bound2D) {
			return this.inside(x.min) && this.inside(x.max);
		} else if (x instanceof Point2D) {
			return between(this.min.x, x.x, this.max.x) && between(this.min.y, x.y, this.max.y);
		}
		return false;
	};
	Bound2D.prototype.intersects = function (item) {
		if (item instanceof Bound2D) {
			var
			ox = overlap(this.min.x, this.max.x, item.min.x, item.max.x),
			oy = overlap(this.min.y, this.max.y, item.min.y, item.max.y);
			return ox && oy;
		} else if (item instanceof Point2D) {
			return this.inside(item);
		}
		return false;
	};
}(window));