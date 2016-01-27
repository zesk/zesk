/*jslint bitwise: true */
/* A JavaScript implementation of the Secure Hash Algorithm, SHA-256
 * Version 0.3 Copyright Angel Marin 2003-2004 - http://anmar.eu.org/
 * Distributed under the BSD License
 * Some bits taken from Paul Johnston's SHA-1 implementation
 */
/*
 * kent -at- marketacumen.com JSLinted and added closure  
 * @param exports
 */
(function(exports) {
	var chrsz = 8; /* bits per input character. 8 - ASCII; 16 - Unicode */
	function safe_add(x, y) {
		var lsw = (x & 0xFFFF) + (y & 0xFFFF);
		var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
		return (msw << 16) | (lsw & 0xFFFF);
	}
	function s(x, n) {
		return (x >>> n) | (x << (32 - n));
	}
	function r(x, n) {
		return (x >>> n);
	}
	function ch(x, y, z) {
		return ((x & y) ^ ((~x) & z));
	}
	function maj(x, y, z) {
		return ((x & y) ^ (x & z) ^ (y & z));
	}
	function sigma0256(x) {
		return (s(x, 2) ^ s(x, 13) ^ s(x, 22));
	}
	function sigma1256(x) {
		return (s(x, 6) ^ s(x, 11) ^ s(x, 25));
	}
	function gamma0256(x) {
		return (s(x, 7) ^ s(x, 18) ^ r(x, 3));
	}
	function gamma1256(x) {
		return (s(x, 17) ^ s(x, 19) ^ r(x, 10));
	}
	function core_sha256(m, l) {
		var K = [ 0x428A2F98, 0x71374491, 0xB5C0FBCF, 0xE9B5DBA5, 0x3956C25B, 0x59F111F1, 0x923F82A4, 0xAB1C5ED5, 0xD807AA98, 0x12835B01, 0x243185BE, 0x550C7DC3, 0x72BE5D74, 0x80DEB1FE, 0x9BDC06A7,
		        0xC19BF174, 0xE49B69C1, 0xEFBE4786, 0xFC19DC6, 0x240CA1CC, 0x2DE92C6F, 0x4A7484AA, 0x5CB0A9DC, 0x76F988DA, 0x983E5152, 0xA831C66D, 0xB00327C8, 0xBF597FC7, 0xC6E00BF3, 0xD5A79147,
		        0x6CA6351, 0x14292967, 0x27B70A85, 0x2E1B2138, 0x4D2C6DFC, 0x53380D13, 0x650A7354, 0x766A0ABB, 0x81C2C92E, 0x92722C85, 0xA2BFE8A1, 0xA81A664B, 0xC24B8B70, 0xC76C51A3, 0xD192E819,
		        0xD6990624, 0xF40E3585, 0x106AA070, 0x19A4C116, 0x1E376C08, 0x2748774C, 0x34B0BCB5, 0x391C0CB3, 0x4ED8AA4A, 0x5B9CCA4F, 0x682E6FF3, 0x748F82EE, 0x78A5636F, 0x84C87814, 0x8CC70208,
		        0x90BEFFFA, 0xA4506CEB, 0xBEF9A3F7, 0xC67178F2 ];
		var HASH = [ 0x6A09E667, 0xBB67AE85, 0x3C6EF372, 0xA54FF53A, 0x510E527F, 0x9B05688C, 0x1F83D9AB, 0x5BE0CD19 ];
		var W = new Array(64);
		var a, b, c, d, e, f, g, h, i, j;
		var T1, T2;
		/* append padding */
		m[l >> 5] |= 0x80 << (24 - l % 32);
		m[((l + 64 >> 9) << 4) + 15] = l;
		for (i = 0; i < m.length; i += 16) {
			a = HASH[0];
			b = HASH[1];
			c = HASH[2];
			d = HASH[3];
			e = HASH[4];
			f = HASH[5];
			g = HASH[6];
			h = HASH[7];
			for (j = 0; j < 64; j++) {
				if (j < 16) {
					W[j] = m[j + i];
				} else {
					W[j] = safe_add(safe_add(safe_add(gamma1256(W[j - 2]), W[j - 7]), gamma0256(W[j - 15])), W[j - 16]);
				}
				T1 = safe_add(safe_add(safe_add(safe_add(h, sigma1256(e)), ch(e, f, g)), K[j]), W[j]);
				T2 = safe_add(sigma0256(a), maj(a, b, c));
				h = g;
				g = f;
				f = e;
				e = safe_add(d, T1);
				d = c;
				c = b;
				b = a;
				a = safe_add(T1, T2);
			}
			HASH[0] = safe_add(a, HASH[0]);
			HASH[1] = safe_add(b, HASH[1]);
			HASH[2] = safe_add(c, HASH[2]);
			HASH[3] = safe_add(d, HASH[3]);
			HASH[4] = safe_add(e, HASH[4]);
			HASH[5] = safe_add(f, HASH[5]);
			HASH[6] = safe_add(g, HASH[6]);
			HASH[7] = safe_add(h, HASH[7]);
		}
		return HASH;
	}
	function str2binb(str) {
		var bin = [];
		var mask = (1 << chrsz) - 1;
		for (var i = 0; i < str.length * chrsz; i += chrsz) {
			bin[i >> 5] |= (str.charCodeAt(i / chrsz) & mask) << (24 - i % 32);
		}
		return bin;
	}
	function binb2hex(binarray) {
		var hexcase = 0; /* hex output format. 0 - lowercase; 1 - uppercase */
		var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
		var str = "";
		var i;
		for (i = 0; i < binarray.length * 4; i++) {
			str += hex_tab.charAt((binarray[i >> 2] >> ((3 - i % 4) * 8 + 4)) & 0xF) + hex_tab.charAt((binarray[i >> 2] >> ((3 - i % 4) * 8)) & 0xF);
		}
		return str;
	}
	exports.hex_sha256 = function(s) {
		return binb2hex(core_sha256(str2binb(s), s.length * chrsz));
	};
	exports.bin_sha256 = function(b) {
		return core_sha256(b, b.length);
	};
	exports.bin_sha256s = function(s) {
		return core_sha256(str2binb(s), s.length * chrsz);
	};
}(window));
