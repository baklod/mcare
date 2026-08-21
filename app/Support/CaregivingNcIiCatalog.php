<?php

namespace App\Support;

class CaregivingNcIiCatalog
{
    public const PROGRAM_CODE = 'caregiving_nc_ii';

    /**
     * The catalog follows the client-provided Achievement and Progress charts.
     * Titles stay editable in the database so MCTC can correct terminology later.
     *
     * @return list<array{category: string, code: ?string, title: string, tor: bool, outcomes: list<string>}>
     */
    public static function units(): array
    {
        return [
            self::unit('basic', 'Participate in Workplace Communication', [
                'Obtain and convey workplace information',
                'Perform duties following workplace instructions',
                'Complete relevant work-related documents',
            ], '500311105'),
            self::unit('basic', 'Work in a Team Environment', [
                'Describe team role and scope',
                "Identify one's role and responsibility within a team",
                'Work as a team member',
            ], '500311106'),
            self::unit('basic', 'Solve and Address General Workplace Problems', [
                'Identify routine problems',
                'Look for solutions to routine problems',
                'Recommend solutions to problems',
            ], '500311107'),
            self::unit('basic', 'Develop Career and Life Decisions', [
                "Manage one's emotion",
                'Develop reflective practice',
                'Boost self-confidence and develop self-regulation',
            ], '500311108'),
            self::unit('basic', 'Contribute to Workplace Innovation', [
                'Identify opportunities to do things better',
                'Discuss and develop ideas with others',
                'Integrate ideas for change in the workplace',
            ], '500311109'),
            self::unit('basic', 'Present Relevant Information', [
                'Gather data and information',
                'Assess gathered data and information',
                'Record and present information',
            ], '500311110'),
            self::unit('basic', 'Practice Occupational Safety and Health Policies and Procedures', [
                'Identify OSH compliance requirements',
                'Prepare OSH requirements for compliance',
                'Perform tasks in accordance with relevant OSH policies and procedures',
            ], '500311111'),
            self::unit('basic', 'Exercise Efficient and Effective Sustainable Practices in the Workplace', [
                'Identify the efficiency and effectiveness of resource utilization',
                'Determine causes of inefficient or ineffective resource utilization',
                'Convey inefficient and ineffective environmental practices',
            ], '500311112'),
            self::unit('basic', 'Practice Entrepreneurial Skills in the Workplace', [
                'Apply entrepreneurial workplace best practices',
                'Communicate entrepreneurial workplace best practices',
                'Implement cost-effective operations',
            ], '500311113'),
            self::unit('common', 'Implement and Monitor Infection Control Policies and Procedures', [
                "Provide information about the organization's infection control policies and procedures",
                "Integrate the organization's infection control policies and procedures into work practices",
                'Monitor infection control performance and implement improvements in practices',
            ], 'HCS323201'),
            self::unit('common', 'Respond Effectively to Difficult or Challenging Behavior', [
                'Plan responses',
                'Apply responses',
                'Report and review incidents',
            ], 'HCS323202'),
            self::unit('common', 'Apply Basic First Aid', [
                'Assess the situation',
                'Apply basic first aid techniques',
                'Communicate details of the incident',
            ], 'HCS323203'),
            self::unit('common', 'Maintain a High Standard of Patient Services', [
                'Communicate appropriately with patients',
                'Establish and maintain good interpersonal relationships with patients',
                'Act in a respectful manner at all times',
                'Evaluate own work to maintain a high standard of patient services',
            ], 'HCS323204'),
            self::unit('core', 'Provide Care and Support to Infants and Toddlers', [
                'Comfort infants and toddlers',
                'Bathe and dress infants and toddlers',
                'Feed infants and toddlers',
                'Put infants and toddlers to sleep',
                'Enhance social, physical, intellectual, creative, and emotional activities of infants and toddlers',
            ], 'HCS323301', true),
            self::unit('core', 'Provide Care and Support to Children', [
                'Instill personal hygiene practices in children',
                'Bathe and dress children',
                'Feed children',
            ], 'HCS323302', true),
            self::unit('core', 'Foster Social, Intellectual, Creative and Emotional Development of Children', [
                "Foster children's independence and autonomy",
                'Encourage children to express their feelings, ideas, and needs',
                "Stimulate children's awareness and creativity",
                "Foster children's self-esteem and development of self-concept",
            ], 'HCS323303', true),
            self::unit('core', 'Foster Physical Development of Children', [
                'Enhance physical activities of children',
                'Create opportunities for children to develop a wider range of physical development',
                'Provide experiences that support the physical development of children',
            ], 'HCS323304', true),
            self::unit('core', 'Provide Care and Support to Elderly', [
                'Establish and maintain an appropriate relationship with the elderly',
                'Provide appropriate support to the elderly',
                "Provide assistance with the elderly's personal care needs",
            ], 'HCS323305', true),
            self::unit('core', 'Provide Care and Support to People with Special Needs', [
                'Establish and maintain an appropriate relationship with people with special needs',
                'Provide appropriate support to people with special needs',
                'Assist in maintaining the well-being of people with special needs',
                'Assist people with special needs to identify and meet their needs',
                'Assist people with special needs in maintaining an environment that enables maximum independent living',
            ], 'HCS323306', true),
            self::unit('core', 'Maintain a Healthy and Safe Environment', [
                'Maintain a clean and hygienic environment',
                'Provide a safe environment',
                'Supervise the safety of clients',
            ], 'HCS323307', true),
            self::unit('core', 'Respond to Emergency', [
                'Implement procedures for infection control',
                'Respond to emergencies and accidents',
                'Administer medication within guidelines',
                'Respond to threats and situations of danger',
            ], 'HCS323308', true),
            self::unit('core', 'Clean Living Room, Dining Room, Bedrooms, Toilet and Bathroom', [
                'Clean surfaces and fixtures',
                'Make up beds and cots',
                'Clean toilet and bathroom',
                'Sanitize rooms',
                'Maintain a clean room environment',
                'Clean kitchen',
            ], 'HCS323309', true),
            self::unit('core', 'Wash and Iron Clothes, Linen and Fabric', [
                'Check and sort clothes, linens, and fabrics',
                'Remove stains',
                'Prepare washing equipment and supplies',
                'Perform laundry',
                'Maintain laundry room, equipment, and machines',
                'Dry clothes, linens, and fabrics',
                'Iron clothes, linens, and fabrics',
            ], 'HCS323310', true),
            self::unit('core', 'Prepare Hot and Cold Meals', [
                'Prepare ingredients according to recipes',
                'Cook meals and dishes according to recipes',
                'Present cooked dishes',
                'Prepare sauces, dressings, and garnishes',
                'Prepare appetizers',
                'Prepare desserts and salads',
                'Prepare sandwiches',
                'Store excess food and ingredients',
            ], 'HCS323311', true),
        ];
    }

    /**
     * @return list<array{category: string, code: string, title: string, outcomes: list<string>}>
     */
    public static function coreUnits(): array
    {
        return collect(self::units())
            ->where('category', 'core')
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $outcomes
     * @return array{category: string, code: ?string, title: string, tor: bool, outcomes: list<string>}
     */
    private static function unit(
        string $category,
        string $title,
        array $outcomes,
        ?string $code = null,
        bool $tor = false,
    ): array {
        return compact('category', 'code', 'title', 'tor', 'outcomes');
    }
}
