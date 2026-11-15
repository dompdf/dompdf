<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Css;

/**
 * A CSS gradient image
 *
 * Supports `linear-gradient()`, `radial-gradient()`, and their repeating
 * variants. Colors are interpolated in sRGB with premultiplied alpha.
 *
 * @link https://www.w3.org/TR/css-images-3/#gradients
 */
class Gradient
{
    /**
     * Number of segments to approximate a color transition with, where it
     * is not linear
     */
    private const SEGMENTS = 8;

    /**
     * Repetitions shorter than this (in pt) are filled with the average
     * color, as the result is not meaningful. 1px at 96 DPI, the resolution
     * dompdf assumes for CSS pixels
     *
     * @link https://www.w3.org/TR/css-images-3/#repeating-gradients
     */
    private const MIN_REPEAT_LENGTH = 0.75;

    /**
     * The start of a gradient value
     */
    private const PATTERN = "/^\s*(repeating-)?(linear|radial)-gradient\(/i";

    /**
     * @var bool
     */
    private $linear;

    /**
     * @var bool
     */
    private $repeat;

    /**
     * The direction of a linear gradient, or the shape and position of a
     * radial gradient, if given
     *
     * @var string|null
     */
    private $config;

    /**
     * Color stops as `[color, position]` and color hints as `[null, position]`
     *
     * @var array[]
     */
    private $stops;

    /**
     * @param string $value
     *
     * @return bool Whether the value is a gradient function supported by {@link Gradient::parse()}.
     *              Its arguments are not validated
     */
    public static function isGradient(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Parse a gradient value
     *
     * Of multiple comma-separated images, only the first is parsed.
     *
     * @param string $value
     *
     * @return self|null The gradient, or null if the value is not a valid gradient
     */
    public static function parse(string $value): ?self
    {
        if (!preg_match(self::PATTERN, $value, $m)) {
            return null;
        }

        // Find the closing parenthesis
        $start = \strlen($m[0]);
        $depth = 1;
        for ($i = $start; $i < \strlen($value) && $depth > 0; $i++) {
            $depth += (int) ($value[$i] === "(") - (int) ($value[$i] === ")");
        }
        $rest = trim(substr($value, $i));
        if ($depth > 0 || ($rest !== "" && $rest[0] !== ",")) {
            return null;
        }

        $gradient = new self();
        $gradient->repeat = $m[1] !== "";
        $gradient->linear = strtolower($m[2]) === "linear";
        // Empty arguments are invalid
        $body = substr($value, $start, $i - $start - 1);
        $args = self::split($body, ",");
        if ($args === [] || preg_match("/^\s*,|,\s*,|,\s*$/", $body)) {
            return null;
        }

        $isConfig = $gradient->linear
            ? preg_match("/^(to\s|[-+]?[\d.]+(deg|grad|rad|turn)$|[-+]?0$)/i", $args[0])
            : preg_match("/^(circle|ellipse|closest-|farthest-|at\s|-?[\d.]|calc\()/i", $args[0]);
        if ($isConfig) {
            $gradient->config = strtolower(array_shift($args));
            $valid = $gradient->linear ? self::isValidDirection($gradient->config) : self::isValidShape($gradient->config);
            if (!$valid) {
                return null;
            }
        }

        // Color stops with up to two positions, and color hints between them
        $gradient->stops = [];
        $hint = false;
        foreach ($args as $arg) {
            $parts = self::split($arg, " \t\r\n");

            if (\count($parts) === 1 && self::isLength($parts[0])) {
                if ($hint || $gradient->stops === []) {
                    return null;
                }
                $gradient->stops[] = [null, $parts[0]];
                $hint = true;
                continue;
            }

            $color = strtolower($parts[0]);
            $parsed = Color::parse($color);
            $valid = $color === "currentcolor" || $parsed === "transparent"
                || (\is_array($parsed) && $parsed[0] !== null);
            $positions = \array_slice($parts, 1);
            if (!$valid || \count($positions) > 2 || \count(array_filter($positions, [self::class, "isLength"])) !== \count($positions)) {
                return null;
            }

            foreach ($positions ?: [null] as $position) {
                $gradient->stops[] = [$color, $position];
            }
            $hint = false;
        }

        return $hint || $gradient->stops === [] ? null : $gradient;
    }

    /**
     * Resolve the gradient for a box
     *
     * Background size, position, and repeat are not applied, the gradient
     * always covers the full box.
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     * @param Style $style The style to resolve lengths and `currentcolor` with
     *
     * @return array `[type, coords, matrix, stops, repeat]`, see {@link \Dompdf\Adapter\CPDF::gradient()}
     */
    public function resolve(float $x, float $y, float $w, float $h, Style $style): array
    {
        if ($this->linear) {
            $a = deg2rad($this->angle($w, $h));
            $length = abs($w * sin($a)) + abs($h * cos($a));
        } else {
            [$cx, $cy, $rx, $ry] = $this->shape($w, $h, $style);
            $length = $rx;
        }

        $stops = $this->resolveStops($length, $style);
        $n = \count($stops);
        $t0 = $stops[0][0];
        $t1 = $stops[$n - 1][0];
        $repeat = $this->repeat;

        // A repetition that is too small to render is filled with the average
        // color. Without length, the stops count as evenly spaced
        if ($repeat && ($t1 - $t0) * $length < self::MIN_REPEAT_LENGTH) {
            $stops = [self::average($stops)];
            $t0 = 0;
            $t1 = 1;
            $repeat = false;
        }

        if (!$repeat) {
            $t0 = min(0, $t0);
            $t1 = max(1, $t1);
        }

        // Map the stops to the range between the first and last stop when
        // repeating, otherwise extend the range to include all stops
        foreach ($stops as &$stop) {
            $stop[0] = ($stop[0] - $t0) / ($t1 - $t0);
        }
        unset($stop);

        if ($this->linear) {
            // The gradient line passes through the center of the box
            $point = function (float $t) use ($x, $y, $w, $h, $a, $length): array {
                return [
                    $x + $w / 2 + sin($a) * ($t - 0.5) * $length,
                    $y + $h / 2 - cos($a) * ($t - 0.5) * $length
                ];
            };
            return [2, array_merge($point($t0), $point($t1)), [1, 0, 0, 1, 0, 0], $stops, $repeat];
        }

        // A circle in gradient space, scaled to the ellipse
        return [3, [0, 0, $t0, 0, 0, $t1], [$rx, 0, 0, $ry, $x + $cx, $y + $cy], $stops, $repeat];
    }

    /**
     * @param float $w
     * @param float $h
     *
     * @return float The angle of a linear gradient in degrees
     */
    private function angle(float $w, float $h): float
    {
        $direction = $this->config ?? "to bottom";

        if (preg_match("/^([-+]?[\d.]+)(deg|grad|rad|turn)?$/", $direction, $m)) {
            $factor = ["deg" => 1, "grad" => 0.9, "rad" => 180 / M_PI, "turn" => 360];
            return (float) $m[1] * $factor[($m[2] ?? "") ?: "deg"];
        }

        // For corners, the angle is chosen so that the line between the two
        // neighboring corners has the color of the 50% point
        $dx = (int) (strpos($direction, "right") !== false) - (int) (strpos($direction, "left") !== false);
        $dy = (int) (strpos($direction, "bottom") !== false) - (int) (strpos($direction, "top") !== false);
        return rad2deg(atan2($dx * $h, -$dy * $w));
    }

    /**
     * @param float $w
     * @param float $h
     * @param Style $style
     *
     * @return float[] The center relative to the box, and the radii of a
     *                 radial gradient, as `[cx, cy, rx, ry]`
     */
    private function shape(float $w, float $h, Style $style): array
    {
        $parts = preg_split("/\bat\b/", $this->config ?? "", 2);
        $shape = self::split($parts[0], " \t\r\n");
        [$x, $y] = self::position(self::split($parts[1] ?? "center", " \t\r\n"));
        $cx = $style->length_in_pt($x, $w);
        $cy = $style->length_in_pt($y, $h);

        // Size: explicit radii, or the distance to the sides or corners
        $lengths = array_values(array_filter($shape, [self::class, "isLength"]));
        $circle = \in_array("circle", $shape, true) || (\count($lengths) === 1 && !\in_array("ellipse", $shape, true));

        if (\count($lengths) > 0) {
            $rx = $style->length_in_pt($lengths[0], $w);
            $ry = $circle ? $rx : $style->length_in_pt($lengths[1] ?? $lengths[0], $h);
        } else {
            $size = array_values(array_intersect($shape, ["closest-side", "farthest-side", "closest-corner", "farthest-corner"]));
            $size = $size[0] ?? "farthest-corner";
            $pick = strncmp($size, "closest", 7) === 0 ? "min" : "max";
            $sx = $pick($cx, $w - $cx);
            $sy = $pick($cy, $h - $cy);

            if (substr($size, -6) === "corner") {
                [$rx, $ry] = $circle ? array_fill(0, 2, hypot($sx, $sy)) : [$sx * M_SQRT2, $sy * M_SQRT2];
            } else {
                [$rx, $ry] = $circle ? array_fill(0, 2, $pick($sx, $sy)) : [$sx, $sy];
            }
        }

        return [$cx, $cy, max($rx, 0.01), max($ry, 0.01)];
    }

    /**
     * Resolve the color stops, including color hints and the interpolation
     * with premultiplied alpha, which are approximated with additional stops
     *
     * @param float $length The length of the gradient line or ray
     * @param Style $style
     *
     * @return array A list of `[offset, [r, g, b], alpha]` with increasing
     *               offsets relative to the gradient length
     *
     * @link https://www.w3.org/TR/css-images-4/#color-stop-fixup
     */
    private function resolveStops(float $length, Style $style): array
    {
        $items = [];
        foreach ($this->stops as [$color, $position]) {
            $offset = $position === null ? null : $style->length_in_pt($position, $length) / ($length ?: 1);
            $items[] = $color === null ? [$offset] : array_merge([$offset], self::color($color, $style));
        }

        // Positions of the first and last stop default to the ends, and
        // positions of stops and hints never decrease
        $n = \count($items);
        $items[0][0] = $items[0][0] ?? 0.0;
        $items[$n - 1][0] = $items[$n - 1][0] ?? 1.0;
        $max = $items[0][0];
        foreach ($items as $i => $item) {
            if ($item[0] !== null) {
                $max = $items[$i][0] = max($max, $item[0]);
            }
        }

        // Stops without position are spread evenly between their neighbors
        for ($i = 1; $i < $n - 1; $i++) {
            if ($items[$i][0] !== null) {
                continue;
            }
            $j = $i + 1;
            while ($items[$j][0] === null) {
                $j++;
            }
            $start = $items[$i - 1][0];
            $step = ($items[$j][0] - $start) / ($j - $i + 1);
            for ($k = $i; $k < $j; $k++) {
                $items[$k][0] = $start + $step * ($k - $i + 1);
            }
        }

        // Stops as `[offset, color, alpha, hint]`, where a fully transparent
        // stop takes the colors of its neighbors
        $stops = [];
        $hint = null;
        foreach ($items as $i => $item) {
            if (\count($item) === 1) {
                $hint = $item[0];
                continue;
            }
            [$offset, $rgb, $alpha] = $item;
            if ($alpha > 0) {
                $stops[] = [$offset, $rgb, $alpha, $hint];
            } else {
                $prev = $items[$i - 1][1] ?? $items[$i - 2][1] ?? $items[$i + 1][1] ?? $items[$i + 2][1] ?? $rgb;
                $next = $items[$i + 1][1] ?? $items[$i + 2][1] ?? $prev;
                $stops[] = [$offset, $prev, 0.0, $hint];
                $stops[] = [$offset, $next, 0.0, null];
            }
            $hint = null;
        }

        // Approximate non-linear transitions with additional stops: with a
        // color hint, or with premultiplied alpha between different colors.
        // The stops are spaced by equal color change
        $result = [\array_slice($stops[0], 0, 3)];
        for ($i = 1; $i < \count($stops); $i++) {
            [$start, $color0, $alpha0] = $stops[$i - 1];
            [$end, $color1, $alpha1, $hint] = $stops[$i];

            $mid = $hint === null || $end <= $start ? 0.5 : ($hint - $start) / ($end - $start);
            if ($mid <= 0 || $mid >= 1) {
                // The color changes abruptly at the hint
                $result[] = $mid <= 0 ? [$start, $color1, $alpha1] : [$end, $color0, $alpha0];
            } elseif ($end > $start && ($hint !== null || ($alpha0 != $alpha1 && $color0 != $color1))) {
                for ($k = 1; $k < self::SEGMENTS; $k++) {
                    $weight = $k / self::SEGMENTS;
                    $t = $weight ** (log($mid) / log(0.5));
                    $result[] = array_merge([$start + $t * ($end - $start)], self::mix($color0, $alpha0, $color1, $alpha1, $weight));
                }
            }
            $result[] = [$end, $color1, $alpha1];
        }

        return $result;
    }

    /**
     * @param string $color
     * @param Style  $style
     *
     * @return array The color as `[[r, g, b], alpha]`
     */
    private static function color(string $color, Style $style): array
    {
        $c = $color === "currentcolor" ? $style->color : Color::parse($color);
        if (!\is_array($c)) {
            return [[0, 0, 0], 0.0];
        }

        // CMYK colors are converted to RGB
        $rgb = $c[3] === null
            ? [$c[0], $c[1], $c[2]]
            : [(1 - $c[0]) * (1 - $c[3]), (1 - $c[1]) * (1 - $c[3]), (1 - $c[2]) * (1 - $c[3])];

        return [$rgb, (float) $c["alpha"]];
    }

    /**
     * Mix two colors with premultiplied alpha
     *
     * @param float[] $color0
     * @param float   $alpha0
     * @param float[] $color1
     * @param float   $alpha1
     * @param float   $weight The weight of the second color
     *
     * @return array The color as `[[r, g, b], alpha]`
     */
    private static function mix(array $color0, float $alpha0, array $color1, float $alpha1, float $weight): array
    {
        $alpha = $alpha0 + ($alpha1 - $alpha0) * $weight;
        $color = [];
        foreach ($color0 as $i => $value) {
            $color[] = $alpha > 0
                ? ($value * $alpha0 * (1 - $weight) + $color1[$i] * $alpha1 * $weight) / $alpha
                : $value + ($color1[$i] - $value) * $weight;
        }

        return [$color, $alpha];
    }

    /**
     * Get the average color of stops, treating them as evenly spaced if they
     * are all at the same position
     *
     * @param array $stops
     *
     * @return array The average as stop at offset 0
     */
    private static function average(array $stops): array
    {
        $even = $stops[\count($stops) - 1][0] <= $stops[0][0];
        $color = [0, 0, 0];
        $alpha = 0;
        $total = 0;
        for ($i = 1; $i < \count($stops); $i++) {
            [$o0, $c0, $a0] = $stops[$i - 1];
            [$o1, $c1, $a1] = $stops[$i];
            $d = $even ? 1 : $o1 - $o0;
            $alpha += $d * ($a0 + $a1) / 2;
            foreach ($color as $k => $v) {
                $color[$k] += $d * ($c0[$k] * $a0 + $c1[$k] * $a1) / 2;
            }
            $total += $d;
        }

        if ($total == 0 || $alpha == 0) {
            return [0, $stops[0][1], $total == 0 ? $stops[0][2] : 0.0];
        }

        return [0, array_map(function ($v) use ($alpha) {
            return $v / $alpha;
        }, $color), $alpha / $total];
    }

    /**
     * @param string $direction
     *
     * @return bool Whether the direction of a linear gradient is valid
     */
    private static function isValidDirection(string $direction): bool
    {
        if (strncmp($direction, "to", 2) !== 0) {
            return true;
        }

        $sides = self::split(substr($direction, 2), " \t\r\n");
        $x = array_intersect($sides, ["left", "right"]);
        $y = array_intersect($sides, ["top", "bottom"]);

        return \count($sides) > 0 && \count($x) <= 1 && \count($y) <= 1 && \count($x) + \count($y) === \count($sides);
    }

    /**
     * @param string $shape
     *
     * @return bool Whether the shape, size, and position of a radial gradient are valid
     */
    private static function isValidShape(string $shape): bool
    {
        $parts = preg_split("/\bat\b/", $shape, 2);
        $tokens = self::split($parts[0], " \t\r\n");
        $lengths = array_filter($tokens, [self::class, "isLength"]);
        $keywords = array_diff($tokens, $lengths);
        $sizes = array_intersect($keywords, ["closest-side", "farthest-side", "closest-corner", "farthest-corner"]);
        $shapes = array_intersect($keywords, ["circle", "ellipse"]);

        // A circle has one non-negative length as radius, an ellipse two
        // lengths or percentages
        $count = \count($lengths);
        $circle = \in_array("circle", $shapes, true) || ($count === 1 && $shapes === []);
        $validLengths = $count === 0 || ($circle
            ? $count === 1 && substr(reset($lengths), -1) !== "%"
            : $count === 2);

        return \count($sizes) + \count($shapes) === \count($keywords)
            && \count($sizes) <= 1 && \count($shapes) <= 1
            && ($count === 0 || $sizes === [])
            && $validLengths
            && preg_grep("/^-/", $lengths) === []
            && self::position(self::split($parts[1] ?? "center", " \t\r\n")) !== null;
    }

    /**
     * Resolve a position of one or two keywords or lengths, or of two
     * keywords with offsets
     *
     * @param string[] $tokens
     *
     * @return string[]|null The horizontal and vertical position as lengths
     *                       or percentages, or null if invalid
     *
     * @link https://www.w3.org/TR/css-values-4/#position
     */
    private static function position(array $tokens): ?array
    {
        $keywords = ["left" => ["x", "0%"], "right" => ["x", "100%"], "top" => ["y", "0%"], "bottom" => ["y", "100%"]];
        $count = \count($tokens);
        $position = [];

        // Offsets from the given sides
        if ($count === 4) {
            foreach ([[$tokens[0], $tokens[1]], [$tokens[2], $tokens[3]]] as [$keyword, $offset]) {
                if (!isset($keywords[$keyword]) || !self::isLength($offset) || isset($position[$keywords[$keyword][0]])) {
                    return null;
                }
                $far = $keyword === "right" || $keyword === "bottom";
                $position[$keywords[$keyword][0]] = $far ? "calc(100% - $offset)" : $offset;
            }
            return [$position["x"], $position["y"]];
        }

        if ($count < 1 || $count > 2) {
            return null;
        }

        // Keywords determine the axis, lengths are horizontal first. The
        // remaining values, including `center`, take the remaining axis
        $remaining = [];
        foreach ($tokens as $i => $token) {
            if (isset($keywords[$token])) {
                [$axis, $value] = $keywords[$token];
                if (isset($position[$axis])) {
                    return null;
                }
                $position[$axis] = $value;
            } elseif ($token === "center" || self::isLength($token)) {
                $remaining[] = [$token === "center" ? null : ($i === 0 ? "x" : "y"), $token === "center" ? "50%" : $token];
            } else {
                return null;
            }
        }
        foreach ($remaining as [$axis, $value]) {
            $axis = $axis ?? (isset($position["x"]) ? "y" : "x");
            if (isset($position[$axis])) {
                return null;
            }
            $position[$axis] = $value;
        }

        return [$position["x"] ?? "50%", $position["y"] ?? "50%"];
    }

    /**
     * @param string $value
     *
     * @return bool Whether the value is a length or percentage
     */
    private static function isLength(string $value): bool
    {
        $units = "px|pt|pc|cm|mm|in|q|em|rem|ex|ch|vw|vh|vmin|vmax|%";
        return (bool) preg_match("/^([-+]?(\d+|\d*\.\d+)($units)|[-+]?0*\.?0+|calc\(.+\))$/i", $value);
    }

    /**
     * Split a value at separators outside of parentheses
     *
     * @param string $value
     * @param string $separators
     *
     * @return string[] The non-empty parts
     */
    private static function split(string $value, string $separators): array
    {
        $parts = [];
        $part = "";
        $depth = 0;
        foreach (preg_split("//", $value, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            if ($depth === 0 && strpos($separators, $char) !== false) {
                $parts[] = $part;
                $part = "";
                continue;
            }
            $depth += (int) ($char === "(") - (int) ($char === ")");
            $part .= $char;
        }
        $parts[] = $part;

        return array_values(array_filter(array_map("trim", $parts), "strlen"));
    }
}
