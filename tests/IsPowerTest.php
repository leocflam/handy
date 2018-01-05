<?php


class IsPowerTest extends TestCase
{
    protected function isPower($value)
    {
        // 1 ^ n = 1, so always true
        if ($value === 1) {
            return true;
        }
        // we can assume max loop number is within 2 ~ sqrt($value)
        // as optimize, when base value < 2, there would be no power base
        $maxLoop = (int) pow($value, 1/2) + 1;
        for ($i=2; $i <= $maxLoop; $i++) {
            $base     = round(pow($value, 1/$i), 2);
            $floor    = round(floor($base), 2);
            if ($floor === $base) {
                return true;
            }
            if ((int) $base <= 1.99) {
                // we know that minimum base is 2, whereas 1 is already found true
                // so we can break early to optimize the speed
                return false;
            }
        }
        return false;
    }

    /** @test **/
    public function test_is_power()
    {
        // 5 * 5 * 5
        $this->assertTrue($this->isPower(125));
        // 3 * 3
        $this->assertTrue($this->isPower(9));
        // 1 * 1 ..... * 1
        $this->assertTrue($this->isPower(1));
        // 3 * 3 .... 9 times
        $this->assertTrue($this->isPower(19683));

        $this->assertTrue($this->isPower(4));
        $this->assertTrue($this->isPower(8));
        $this->assertTrue($this->isPower(16));

        // no base available for sure
        $this->assertFalse($this->isPower(2));
        $this->assertFalse($this->isPower(3));
        $this->assertFalse($this->isPower(5));
        $this->assertFalse($this->isPower(6));
        $this->assertFalse($this->isPower(7));
        $this->assertFalse($this->isPower(10));

        // 99 * 99 * 99
        $this->assertTrue($this->isPower(970299));

        // 44 * 44 * 44 * 44 = 3748096
        $this->assertTrue($this->isPower(3748096));

        // random number, false!
        $this->assertFalse($this->isPower(378294));
        $this->assertFalse($this->isPower(203480259823475092374956070));
    }
}
