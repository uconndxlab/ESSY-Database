<?php

namespace App\Constants;

use App\Models\ReportData;

class ReportDataFields
{
    // Domain fields
    public const A_DOMAIN = 'A_DOMAIN';
    public const ATT_DOMAIN = 'ATT_DOMAIN';
    public const B_DOMAIN = 'B_DOMAIN';
    public const P_DOMAIN = 'P_DOMAIN';
    public const S_DOMAIN = 'S_DOMAIN';
    public const O_DOMAIN = 'O_DOMAIN';

    // Academic Skills fields
    public const A_READ = 'A_READ';
    public const A_WRITE = 'A_WRITE';
    public const A_MATH = 'A_MATH';
    public const A_P_S_ARTICULATE_CL1 = 'A_P_S_ARTICULATE_CL1';
    public const A_S_ADULTCOMM_CL1 = 'A_S_ADULTCOMM_CL1';
    public const A_DIRECTIONS = 'A_DIRECTIONS';
    public const A_INITIATE = 'A_INITIATE';
    public const A_PLANORG = 'A_PLANORG';
    public const A_TURNIN = 'A_TURNIN';
    public const A_B_CLASSEXPECT_CL1 = 'A_B_CLASSEXPECT_CL1';
    public const A_B_IMPULSE_CL1 = 'A_B_IMPULSE_CL1';
    public const A_ENGAGE = 'A_ENGAGE';
    public const A_INTEREST = 'A_INTEREST';
    public const A_PERSIST = 'A_PERSIST';
    public const A_GROWTH = 'A_GROWTH';
    public const A_S_CONFIDENT_CL1 = 'A_S_CONFIDENT_CL1';
    public const A_S_POSOUT_CL1 = 'A_S_POSOUT_CL1';
    public const A_S_O_ACTIVITY_CL1 = 'A_S_O_ACTIVITY_CL1';

    // Behavior fields
    public const A_B_CLASSEXPECT_CL2 = 'A_B_CLASSEXPECT_CL2';
    public const A_B_IMPULSE_CL2 = 'A_B_IMPULSE_CL2';
    public const B_CLINGY = 'B_CLINGY';
    public const B_SNEAK = 'B_SNEAK';
    public const BEH_VERBAGGRESS = 'BEH_VERBAGGRESS';
    public const BEH_PHYSAGGRESS = 'BEH_PHYSAGGRESS';
    public const B_DESTRUCT = 'B_DESTRUCT';
    public const B_BULLY = 'B_BULLY';
    public const B_PUNITIVE = 'B_PUNITIVE';
    public const B_O_HOUSING_CL1 = 'B_O_HOUSING_CL1';
    public const B_O_FAMSTRESS_CL1 = 'B_O_FAMSTRESS_CL1';
    public const B_O_NBHDSTRESS_CL1 = 'B_O_NBHDSTRESS_CL1';

    // Physical Health fields
    public const P_SIGHT = 'P_SIGHT';
    public const P_HEAR = 'P_HEAR';
    public const A_P_S_ARTICULATE_CL2 = 'A_P_S_ARTICULATE_CL2';
    public const A_ORAL = 'A_ORAL';
    public const A_PHYS = 'A_PHYS';
    public const P_PARTICIPATE = 'P_PARTICIPATE';
    public const S_P_ACHES_CL1 = 'S_P_ACHES_CL1';
    public const O_P_HUNGER_CL1 = 'O_P_HUNGER_CL1';
    public const O_P_hygiene_CL1 = 'O_P_hygiene_CL1';
    public const O_P_CLOTHES_CL1 = 'O_P_CLOTHES_CL1';

    // Social & Emotional Well-Being fields
    public const S_CONTENT = 'S_CONTENT';
    public const A_S_CONFIDENT_CL2 = 'A_S_CONFIDENT_CL2';
    public const A_S_POSOUT_CL2 = 'A_S_POSOUT_CL2';
    public const S_P_ACHES_CL2 = 'S_P_ACHES_CL2';
    public const S_NERVOUS = 'S_NERVOUS';
    public const S_SAD = 'S_SAD';
    public const S_SOCIALCONN = 'S_SOCIALCONN';
    public const S_FRIEND = 'S_FRIEND';
    public const S_PROSOCIAL = 'S_PROSOCIAL';
    public const S_PEERCOMM = 'S_PEERCOMM';
    public const A_S_ADULTCOMM_CL2 = 'A_S_ADULTCOMM_CL2';
    public const S_POSADULT = 'S_POSADULT';
    public const S_SCHOOLCONN = 'S_SCHOOLCONN';
    public const S_COMMCONN = 'S_COMMCONN';
    public const A_S_O_ACTIVITY_CL2 = 'A_S_O_ACTIVITY_CL2';

    // Supports Outside of School fields
    public const O_RECIPROCAL = 'O_RECIPROCAL';
    public const O_POSADULT = 'O_POSADULT';
    public const O_ADULTBEST = 'O_ADULTBEST';
    public const O_TALK = 'O_TALK';
    public const O_ROUTINE = 'O_ROUTINE';
    public const O_FAMILY = 'O_FAMILY';
    public const O_P_HUNGER_CL2 = 'O_P_HUNGER_CL2';
    public const O_P_HYGIENE_CL2 = 'O_P_HYGIENE_CL2'; // Note: correct spelling
    public const O_P_CLOTHES_CL2 = 'O_P_CLOTHES_CL2';
    public const O_RESOURCE = 'O_RESOURCE';
    public const B_O_HOUSING_CL2 = 'B_O_HOUSING_CL2';
    public const B_O_FAMSTRESS_CL2 = 'B_O_FAMSTRESS_CL2';
    public const B_O_NBHDSTRESS_CL2 = 'B_O_NBHDSTRESS_CL2';
    public const A_S_O_ACTIVITY_CL3 = 'A_S_O_ACTIVITY_CL3';

    // Essential items
    public const E_SHARM = 'E_SHARM';
    public const E_BULLIED = 'E_BULLIED';
    public const E_EXCLUDE = 'E_EXCLUDE';
    public const E_WITHDRAW = 'E_WITHDRAW';
    public const E_REGULATE = 'E_REGULATE';
    public const E_RESTED = 'E_RESTED';

    // Student/Teacher info
    public const FN_STUDENT = 'FN_STUDENT';
    public const LN_STUDENT = 'LN_STUDENT';
    public const FN_TEACHER = 'FN_TEACHER';
    public const LN_TEACHER = 'LN_TEACHER';
    public const SCHOOL = 'SCHOOL';

    // Demographics
    public const DEM_RACE = 'DEM_RACE';
    public const DEM_ETHNIC = 'DEM_ETHNIC';
    public const DEM_GENDER = 'DEM_GENDER';
    public const DEM_ELL = 'DEM_ELL';
    public const DEM_IEP = 'DEM_IEP';
    public const DEM_504 = 'DEM_504';
    public const DEM_CI = 'DEM_CI';
    public const DEM_GRADE = 'DEM_GRADE';
    public const DEM_CLASSTEACH = 'DEM_CLASSTEACH';

    /**
     * Get all field constants as an array
     */
    public static function getAllFields(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return array_values($reflection->getConstants());
    }

    /**
     * Validate if a field name exists in the constants
     */
    public static function isValidField(string $field): bool
    {
        return in_array($field, self::getAllFields());
    }

    /**
     * Validate field against ReportData model
     */
    public static function validateAgainstModel(string $field): bool
    {
        $model = new ReportData();
        return in_array($field, $model->getFillable());
    }

    /**
     * Get cross-loaded field groups
     */
    public static function getCrossLoadedGroups(): array
    {
        return [
            'articulate_clearly' => [self::A_P_S_ARTICULATE_CL1, self::A_P_S_ARTICULATE_CL2],
            'communicate_adults' => [self::A_S_ADULTCOMM_CL1, self::A_S_ADULTCOMM_CL2],
            'classroom_expectations' => [self::A_B_CLASSEXPECT_CL1, self::A_B_CLASSEXPECT_CL2],
            'impulsivity' => [self::A_B_IMPULSE_CL1, self::A_B_IMPULSE_CL2],
            'confidence' => [self::A_S_CONFIDENT_CL1, self::A_S_CONFIDENT_CL2],
            'positive_outlook' => [self::A_S_POSOUT_CL1, self::A_S_POSOUT_CL2],
            'aches_pains' => [self::S_P_ACHES_CL1, self::S_P_ACHES_CL2],
            'housing_stability' => [self::B_O_HOUSING_CL1, self::B_O_HOUSING_CL2],
            'family_stress' => [self::B_O_FAMSTRESS_CL1, self::B_O_FAMSTRESS_CL2],
            'neighborhood_stress' => [self::B_O_NBHDSTRESS_CL1, self::B_O_NBHDSTRESS_CL2],
            'hunger' => [self::O_P_HUNGER_CL1, self::O_P_HUNGER_CL2],
            'hygiene' => [self::O_P_hygiene_CL1, self::O_P_HYGIENE_CL2],
            'clothing' => [self::O_P_CLOTHES_CL1, self::O_P_CLOTHES_CL2],
            'extracurricular' => [self::A_S_O_ACTIVITY_CL1, self::A_S_O_ACTIVITY_CL2, self::A_S_O_ACTIVITY_CL3]
        ];
    }

    /**
     * Get domain mapping for fields
     */
    public static function getDomainMapping(): array
    {
        return [
            // Academic Skills
            self::A_READ => 'Academic Skills',
            self::A_WRITE => 'Academic Skills',
            self::A_MATH => 'Academic Skills',
            self::A_P_S_ARTICULATE_CL1 => 'Academic Skills',
            self::A_S_ADULTCOMM_CL1 => 'Academic Skills',
            self::A_DIRECTIONS => 'Academic Skills',
            self::A_INITIATE => 'Academic Skills',
            self::A_PLANORG => 'Academic Skills',
            self::A_TURNIN => 'Academic Skills',
            self::A_B_CLASSEXPECT_CL1 => 'Academic Skills',
            self::A_B_IMPULSE_CL1 => 'Academic Skills',
            self::A_ENGAGE => 'Academic Skills',
            self::A_INTEREST => 'Academic Skills',
            self::A_PERSIST => 'Academic Skills',
            self::A_GROWTH => 'Academic Skills',
            self::A_S_CONFIDENT_CL1 => 'Academic Skills',
            self::A_S_POSOUT_CL1 => 'Academic Skills',
            self::A_S_O_ACTIVITY_CL1 => 'Academic Skills',

            // Behavior
            self::A_B_CLASSEXPECT_CL2 => 'Behavior',
            self::A_B_IMPULSE_CL2 => 'Behavior',
            self::B_CLINGY => 'Behavior',
            self::B_SNEAK => 'Behavior',
            self::BEH_VERBAGGRESS => 'Behavior',
            self::BEH_PHYSAGGRESS => 'Behavior',
            self::B_DESTRUCT => 'Behavior',
            self::B_BULLY => 'Behavior',
            self::B_PUNITIVE => 'Behavior',
            self::B_O_HOUSING_CL1 => 'Behavior',
            self::B_O_FAMSTRESS_CL1 => 'Behavior',
            self::B_O_NBHDSTRESS_CL1 => 'Behavior',

            // Physical Health
            self::P_SIGHT => 'Physical Health',
            self::P_HEAR => 'Physical Health',
            self::A_P_S_ARTICULATE_CL2 => 'Physical Health',
            self::A_ORAL => 'Physical Health',
            self::A_PHYS => 'Physical Health',
            self::P_PARTICIPATE => 'Physical Health',
            self::S_P_ACHES_CL1 => 'Physical Health',
            self::O_P_HUNGER_CL1 => 'Physical Health',
            self::O_P_hygiene_CL1 => 'Physical Health',
            self::O_P_CLOTHES_CL1 => 'Physical Health',

            // Social & Emotional Well-Being
            self::S_CONTENT => 'Social & Emotional Well-Being',
            self::A_S_CONFIDENT_CL2 => 'Social & Emotional Well-Being',
            self::A_S_POSOUT_CL2 => 'Social & Emotional Well-Being',
            self::S_P_ACHES_CL2 => 'Social & Emotional Well-Being',
            self::S_NERVOUS => 'Social & Emotional Well-Being',
            self::S_SAD => 'Social & Emotional Well-Being',
            self::S_SOCIALCONN => 'Social & Emotional Well-Being',
            self::S_FRIEND => 'Social & Emotional Well-Being',
            self::S_PROSOCIAL => 'Social & Emotional Well-Being',
            self::S_PEERCOMM => 'Social & Emotional Well-Being',
            self::A_S_ADULTCOMM_CL2 => 'Social & Emotional Well-Being',
            self::S_POSADULT => 'Social & Emotional Well-Being',
            self::S_SCHOOLCONN => 'Social & Emotional Well-Being',
            self::S_COMMCONN => 'Social & Emotional Well-Being',
            self::A_S_O_ACTIVITY_CL2 => 'Social & Emotional Well-Being',

            // Supports Outside of School
            self::O_RECIPROCAL => 'Supports Outside of School',
            self::O_POSADULT => 'Supports Outside of School',
            self::O_ADULTBEST => 'Supports Outside of School',
            self::O_TALK => 'Supports Outside of School',
            self::O_ROUTINE => 'Supports Outside of School',
            self::O_FAMILY => 'Supports Outside of School',
            self::O_P_HUNGER_CL2 => 'Supports Outside of School',
            self::O_P_HYGIENE_CL2 => 'Supports Outside of School',
            self::O_P_CLOTHES_CL2 => 'Supports Outside of School',
            self::O_RESOURCE => 'Supports Outside of School',
            self::B_O_HOUSING_CL2 => 'Supports Outside of School',
            self::B_O_FAMSTRESS_CL2 => 'Supports Outside of School',
            self::B_O_NBHDSTRESS_CL2 => 'Supports Outside of School',
            self::A_S_O_ACTIVITY_CL3 => 'Supports Outside of School',
        ];
    }
}