<?php

/**
 * Class ddbToMw
 * Convert DDB character sheet JSON to MW character sheet JSON for import into MW
 */
class ddbToMw
{
    protected $ddbData = [];
    protected $mwData = [];
    protected $data = [];
    protected $filename = 'DDB_to_MW';
    protected $alignment = [1=>'LG', 2=>'NG', 3=>'CG', 4=>'LN', 5=>'N', 6=>'CN', 7=>'LE', 8=>'NE', 9=>'CE'];
    protected $abilities = [0=>'strength',1=>'dexterity',2=>'constitution',3=>'intelligence',4=>'wisdom',5=>'charisma'];
    protected $ability_boosts = [
        'strength' => null,
        'dexterity' => null,
        'constitution' => null,
        'intelligence' => null,
        'wisdom' => null,
        'charisma' => null,
    ];
    protected $skills = [
        'strength'=>['athletics'],
        'dexterity'=>['acrobatics','sleight_of_hand','stealth'],
        'constitution'=>[],
        'intelligence'=>['arcana','history','investigation','nature','religion'],
        'wisdom'=>['animal_handling','medicine','perception','insight','survival'],
        'charisma'=>['deception','intimidation','performance','persuasion']
    ];
    protected $spells = [
        0 => [],
        1 => [],
        2 => [],
        3 => [],
        4 => [],
        5 => [],
        6 => [],
        7 => [],
        8 => []
    ];
    protected $classes = [];
    protected $levels = [];
    protected $totalLevel = 0;
    protected $profMod = 2;
    protected $maxWeapons = 5;
    protected $weapons = 0;
    protected $maxEquipment = 20;
    protected $equipment = 0;
    protected $proficiencies = ['saves' => [], 'skills' => [], 'tools' => [], 'weapons' => [], 'armor' => [], 'languages' => []];
    protected $features = ['race' => [], 'class' => [], 'background' => []];

    public function __construct($ddbJson)
    {
        $this->ddbData = json_decode($ddbJson);

        if ($this->ddbData->character->name)
            $this->filename = $this->sanitizeFileName($this->ddbData->character->name);

        $this->data = [
            '_meta_sheet_data_version' => '1',
            'name' => $this->ddbData->character->name,
            'player' => '',
            'campaign' => '',
            'race' => $this->ddbData->character->race->fullName,
            'deity' => $this->ddbData->character->faith,
            'caster' => 0,
            'experience' => $this->ddbData->character->currentXp,
            'next_level' => '',
            'inspiration' => $this->ddbData->character->inspiration ? 1 : 0,
            'background' => $this->ddbData->character->background->definition->name,
            'alignment' => $this->alignment[$this->ddbData->character->alignmentId],
            'speed' => 30,
            'mph' => 3,
            'mpd' => 24,

            'currency_cp' => $this->ddbData->character->currencies->cp ? $this->ddbData->character->currencies->cp : 0,
            'currency_sp' => $this->ddbData->character->currencies->sp ? $this->ddbData->character->currencies->sp : 0,
            'currency_ep' => $this->ddbData->character->currencies->ep ? $this->ddbData->character->currencies->ep : 0,
            'currency_gp' => $this->ddbData->character->currencies->gp ? $this->ddbData->character->currencies->gp : 0,
            'currency_pp' => $this->ddbData->character->currencies->pp ? $this->ddbData->character->currencies->pp : 0,

            //'spell_1_slots' => '3',
            //'spell_1_slots_left' => '0',

            'character_portrait' => $this->ddbData->character->avatarUrl,
            'height' => $this->ddbData->character->height,
            'weight' => $this->ddbData->character->weight,
            'age' => $this->ddbData->character->age,
            'gender' => $this->ddbData->character->gender,
            'hair_color' => $this->ddbData->character->hair,
            'eyes_color' => $this->ddbData->character->eyes,
            'skin_color' => $this->ddbData->character->skin,

            '__txt_personality' => $this->ddbData->character->traits->personalityTraits,
            '__txt_ideals' => $this->ddbData->character->traits->ideals,
            '__txt_bonds' => $this->ddbData->character->traits->bonds,
            '__txt_flaws' => $this->ddbData->character->traits->flaws,

            '__txt_character_1_name' => 'Appearance',
            '__txt_character_1' => $this->ddbData->character->traits->appearance,
            '__txt_character_2_name' => 'Backstory',
            '__txt_character_2' => $this->ddbData->character->notes->backstory,
            '__txt_character_3_name' => 'Allies',
            '__txt_character_3' => $this->ddbData->character->notes->allies,
            '__txt_character_4_name' => 'Enemies',
            '__txt_character_4' => $this->ddbData->character->notes->enemies,
            '__txt_character_5_name' => 'Organizations',
            '__txt_character_5' => $this->ddbData->character->notes->organizations,
            '__txt_character_6_name' => 'Other Holdings',
            '__txt_character_6' => $this->ddbData->character->notes->otherHoldings,
            '__txt_statblock' => '',
            '__txt_other_notes' => $this->ddbData->character->notes->otherNotes . "\n\n### Personal Possessions\n" . $this->ddbData->character->notes->personalPossessions,
            '__txt_private_notes' => '',
        ];

        $this->raceData();
        $this->backgroundData();
        $this->classData();
        $this->setFeatures();
        $this->abilities();
        $this->calcModifiers();
        $this->calcHp();
        $this->calcArmor();
        $this->calcMiscAbilityMods();
        $this->equipment();
        $this->attacks();
        $this->setProficiencies();
        $this->setLanguages();
        $this->setSpells();

        $this->setData();
    }

    public function raceData()
    {
        foreach ($this->ddbData->character->race->racialTraits as $trait)
        {
            if (!$trait->definition->hideInSheet)
                $this->features['race'][] = $trait->definition->name;
        }

        foreach ($this->ddbData->character->choices->race as $trait)
        {
            if ($trait->label == 'Choose a Language')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['languages'][] = $option->label;
                }
            }

            if ($trait->label == 'Choose a Skill')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['skills'][] = $option->label;
                }
            }

            if ($trait->label == 'Choose a Tool')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['tools'][] = $option->label;
                }
            }

            if ($trait->label == 'Choose an Ability Score')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                    {
                        foreach ($this->abilities as $ability)
                        {
                            if (preg_match('#'.$ability.'#', strtolower($option->label)))
                                $this->ability_boosts[$ability]++;
                        }
                    }

                }
            }

            if ($trait->label == 'Choose a Tool')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['tools'][] = $option->label;
                }
            }
        }
    }

    public function backgroundData()
    {
        $this->features['background'][] = 'Feature: '. $this->ddbData->character->background->definition->featureName;

        foreach ($this->ddbData->character->choices->background as $trait)
        {
            if ($trait->label == 'Choose a Language')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['languages'][] = $option->label;
                }
            }

            if ($trait->label == 'Choose a Skill')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['skills'][] = $option->label;
                }
            }

            if ($trait->label == 'Choose a Tool')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['tools'][] = $option->label;
                }
            }
        }
    }

    public function classData()
    {
        foreach ($this->ddbData->character->classes as $class)
        {
            $this->classes[] = $class->definition->name;
            $this->levels[] = $class->level;
            $this->totalLevel += $class->level;

            foreach ($class->classFeatures as $trait)
            {
                if (!$trait->definition->hideInSheet && $trait->definition->requiredLevel <= $class->level)
                    $this->features['class'][] = $trait->definition->name;

                if ($trait->definition->name == 'Proficiencies')
                {
                    $profs = [];
                    $profsRaw = str_replace('<br>', "\n", $trait->definition->description);
                    $profsArray = explode("\n", $profsRaw);
                    foreach ($profsArray as $prof)
                    {
                        $profParts = explode(': ', $prof);
                        $profs[strtolower(strip_tags($profParts[0]))] = strtolower(strip_tags($profParts[1]));
                    }

                    if (isset($profs['armor']))
                        $this->proficiencies['armor'] = explode(', ', $profs['armor']);

                    if (isset($profs['weapons']))
                        $this->proficiencies['weapons'] = explode(', ', $profs['weapons']);

                    if (isset($profs['tools']))
                        $this->proficiencies['tools'] = explode(', ', $profs['tools']);

                    if (isset($profs['saving throws']))
                        $this->proficiencies['saves'] = explode(', ', $profs['saving throws']);
                }
            }
        }

        foreach ($this->ddbData->character->choices->class as $trait)
        {
            if ($trait->label == 'Choose a Language')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['languages'][] = $option->label;
                }
            }

            if (preg_match('#Skill#', $trait->label))
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['skills'][] = $option->label;
                }
            }

            if ($trait->label == 'Choose a Tool')
            {
                foreach ($trait->options as $option)
                {
                    if ($trait->optionValue == $option->id)
                        $this->proficiencies['tools'][] = $option->label;
                }
            }
        }

        if($this->totalLevel < 5)
            $this->profMod = 2;
        elseif($this->totalLevel < 9)
            $this->profMod = 3;
        elseif($this->totalLevel < 13)
            $this->profMod = 4;
        elseif($this->totalLevel < 17)
            $this->profMod = 5;
        else
            $this->profMod = 6;

        $this->data['class'] = implode(' / ', $this->classes);
        $this->data['level'] = implode(' / ', $this->levels);
        $this->data['proficiency_bonus'] = '+'.$this->profMod;
    }

    public function setFeatures()
    {
        $this->data['__txt_features_traits'] = '';
        $this->data['__txt_features_traits'] .= "### Race ######\n" . implode("\n", $this->features['race']) . "\n\n";
        $this->data['__txt_features_traits'] .= "### Background ######\n" . implode("\n", $this->features['background']) . "\n\n";
        $this->data['__txt_features_traits'] .= "### Class ######\n" . implode("\n", $this->features['class']) . "\n\n";
    }

    public function setProficiencies()
    {
        $allProfs = array_merge($this->proficiencies['armor'], $this->proficiencies['weapons'], $this->proficiencies['tools']);
        $counter = ceil(count($allProfs) / 12);
        $allProfs = array_chunk($allProfs, $counter);

        foreach ($allProfs as $index => $allProfChunk)
        {
            $this->data['proficiency_'.($index+1)] = implode(', ', $allProfChunk);
        }

        foreach ($this->proficiencies['saves'] as $save)
        {
            $this->data[$save.'_save_cc'] = 1;

            foreach ($this->abilities as $ability)
            {
                if ($ability == $save)
                {
                    $this->data[$ability.'_save'] += $this->profMod;
                    $this->data[$ability.'_save'] = $this->data[$ability.'_save'] < 0 ? '-' : '+' . $this->data[$ability.'_save'];
                }
            }
        }

        foreach ($this->proficiencies['skills'] as $skill)
        {
            $skill = strtolower(str_replace(' ', '_', $skill));
            $this->data[$skill.'_cc'] = 1;

            foreach ($this->abilities as $ability)
            {
                if ($ability == $skill)
                {
                    $this->data[$skill.'_mod'] += $this->profMod;
                    $this->data[$skill.'_mod'] = $this->data[$skill.'_mod'] < 0 ? '-' : '+' . $this->data[$skill.'_mod'];
                }
            }
        }
    }

    public function setLanguages()
    {
        $allProfs = $this->proficiencies['languages'];
        $counter = ceil(count($allProfs) / 10);
        $allProfs = array_chunk($allProfs, $counter);

        foreach ($allProfs as $index => $allProfChunk)
        {
            $this->data['language_'.($index+1)] = implode(', ', $allProfChunk);
        }
    }

    public function setSpells()
    {
        // From Class
        foreach ($this->ddbData->character->classSpells as $key => $options)
        {
            foreach ($this->ddbData->character->classSpells[$key]->spells as $spell)
            {
                if ($spell->prepared || $spell->alwaysPrepared || $spell->definition->level == 0)
                {
                    $this->spells[$spell->definition->level][] = $spell->definition->name . ' (class)';
                }
            }
        }

        // From other sources
        foreach ($this->ddbData->character->spells as $key => $options)
        {
            foreach ($options as $spell)
            {
                $this->spells[$spell->definition->level][] = $spell->definition->name . ' ('.$key.')';
            }
        }

        foreach ($this->spells as $level => $spells)
        {
            asort($spells);
            foreach ($spells as $key => $spell)
            {
                if ($level > 0)
                {
                    isset($this->data['spell_'.$level.'_prepared']) ? $this->data['spell_'.$level.'_prepared']++ : $this->data['spell_'.$level.'_prepared'] = 1;
                    $this->data['spell_'.$level.'_'.($key +1).'_mem'] = 1;
                }

                $this->data['spell_'.$level.'_'.($key +1)] = $spell;
            }
        }

        // Check the 'Caster' box if we have spells
        if (count($this->spells) > 0)
            $this->data['caster'] = 1;

        // Set SpellCasting Ability/DC/Attack
        $sc_abilities = [];
        $sc_dcs = [];
        $sc_attacks = [];
        foreach ($this->ddbData->character->classes as $class)
        {
            if ($class->definition->canCastSpells)
            {
                $ability = $this->abilities[($class->definition->spellCastingAbilityId - 1)];
                $sc_abilities[] = substr(ucfirst($ability), 0, 3);
                $sc_dcs[] = 8 + $this->profMod + $this->data[$ability.'_mod'];
                $sc_attacks[] = $this->profMod + $this->data[$ability.'_mod'];
            }
        }
        if (!empty($sc_abilities))
            $this->data['casting_ability'] = implode('/', $sc_abilities);
        if (!empty($sc_dcs))
            $this->data['save_dc'] = implode('/', $sc_dcs);
        if (!empty($sc_attacks))
            $this->data['attack_bonus'] = implode('/', $sc_attacks);
    }

    public function abilities()
    {
        foreach ($this->ddbData->character->stats as $key => $stat)
        {
            if ($this->ddbData->character->overrideStats[$key]->value)
                $this->data[$this->abilities[$key]] = $this->ddbData->character->overrideStats[$key]->value;
            else
            {
                $this->data[$this->abilities[$key]] = $stat->value;

                if ($this->ability_boosts[$this->abilities[$key]])
                    $this->data[$this->abilities[$key]] += $this->ability_boosts[$this->abilities[$key]];

                if ($this->ddbData->character->bonusStats[$key]->value)
                    $this->data[$this->abilities[$key]] += $this->ddbData->character->bonusStats[$key]->value;

                // Check for modifiers
                foreach ($this->ddbData->character->modifiers as $modifierGroup)
                {
                    foreach ($modifierGroup as $modifier)
                    {
                        if (preg_match('#score#', $modifier->subType) && $modifier->type == 'bonus')
                        {
                            if (($modifier->entityId - 1) == $key)
                                $this->data[$this->abilities[$key]] += $modifier->value;
                        }

                    }
                }
            }
        }
    }

    public function calcModifiers()
    {
        foreach ($this->abilities as $ability)
        {
            $mod = floor(($this->data[$ability] - 10) / 2);

            $this->data[$ability.'_mod'] = $mod;
            $this->data[$ability.'_save'] = $mod < 0 ? '-' : '+' . $mod;

            foreach ($this->skills[$ability] as $skill)
            {
                $this->data[$skill.'_mod'] = $mod < 0 ? '-' : '+' . $mod;
            }
        }
    }

    public function calcHp()
    {
        $this->data['hp'] = $this->ddbData->character->baseHitPoints + ($this->data['constitution_mod'] * $this->totalLevel);
        $this->data['max_hp'] = $this->data['hp'];
        $this->data['temp_hp'] = 0;
        $this->data['hit_dice'] = '';

        $hd = [];
        $hitdice = [];
        foreach ($this->ddbData->character->classes as $class)
            isset($hd[$class->definition->hitDice]) ? $hd[$class->definition->hitDice] += $class->level : $hd[$class->definition->hitDice] = $class->level;

        foreach ($hd as $die => $count)
            $hitdice[] = $count.'d'.$die;

        $this->data['hit_dice'] = implode('/', $hitdice);
    }

    public function calcArmor()
    {
        $armor = 10;
        $shield = 0;
        $dex = $this->data['dexterity_mod'];
        $other = 0;
        foreach ($this->ddbData->character->inventory as $item)
        {
            if ($item->definition->filterType == 'Armor' && $item->equipped)
            {
                switch($item->definition->type)
                {
                    case 'Shield':
                        $shield = $item->definition->armorClass;
                        break;
                    case 'Heavy Armor':
                        $armor = $item->definition->armorClass;
                        $dex = 0;
                        break;
                    case 'Medium Armor':
                        $armor = $item->definition->armorClass;
                        $dex = $dex > 2 ? 2 : $dex;
                        break;
                    default:
                        $armor = $item->definition->armorClass;
                }
            }

            // Need to filter for other items that are not Armor (ring of protection, etc)
        }

        // Other items can alter AC - feats, class abilities

        $this->data['armor_class'] = $armor + $shield + $dex + $other;
    }

    public function calcMiscAbilityMods()
    {
        $this->data['initiative'] = $this->data['dexterity_mod'] < 0 ? '' : '+' . $this->data['dexterity_mod'];
        $this->data['passive_perception'] = 10 + $this->data['perception_mod'];
    }

    public function equipment()
    {
        foreach ($this->ddbData->character->inventory as $key => $inventory)
        {
            if ($this->equipment < $this->maxEquipment) // only 20 fields allowed
            {
                $this->equipment++;

                $this->data['equip'.$this->equipment.'_'] = $inventory->definition->name;
                $this->data['equip'.$this->equipment.'_weight'] = $inventory->definition->weight;
                $this->data['equip'.$this->equipment.'_worth'] = $inventory->definition->cost;
            }
        }
    }

    public function attacks()
    {
        foreach ($this->ddbData->character->inventory as $key => $inventory)
        {
            if ($this->weapons < $this->maxWeapons && $inventory->definition->filterType == 'Weapon' && $inventory->equipped == true)
            {
                $this->weapons++;

                if ($inventory->definition->range != $inventory->definition->longRange)
                    $range = $inventory->definition->range.'/'.$inventory->definition->longRange;
                else
                    $range = $inventory->definition->range;

                if ($inventory->definition->attackType == 1)
                    $attack = $this->data['strength_mod'] + $this->profMod;
                elseif ($inventory->definition->attackType == 2)
                    $attack = $this->data['dexterity_mod'] + $this->profMod;
                else
                    $attack = null;

                $this->data['weapon_'.$this->weapons.'_name'] = $inventory->definition->name . ' ('.$inventory->definition->damageType[0].'; '.$range.')';
                $this->data['weapon_'.$this->weapons.'_attack'] = $attack && $attack < 0 ? '-' : '+' . $attack;
                $this->data['weapon_'.$this->weapons.'_dmg'] = $inventory->definition->damage->diceString;
            }
        }

        foreach ($this->ddbData->character->spells->class as $spell)
        {
            if ($this->weapons < $this->maxWeapons)
            {
                $this->weapons++;

                $this->data['weapon_'.$this->weapons.'_name'] = $spell->definition->name . ' ('.$spell->definition->modifiers[0]->friendlySubtypeName.'; '.$spell->range->rangeValue.')';
                $this->data['weapon_'.$this->weapons.'_attack'] = '';
                $this->data['weapon_'.$this->weapons.'_dmg'] = $spell->definition->modifiers[0]->die->diceString;
            }
        }
    }

    private function sanitizeFileName($dangerousFilename)
    {
        $dangerousCharacters = array(" ", '"', "'", "&", "/", "\\", "?", "#");

        // every forbidden character is replace by an underscore
        return strtolower(str_replace($dangerousCharacters, '_', $dangerousFilename));
    }

    /*********    Exports     ********/

    public function setData()
    {
        $this->mwData = [
            'id'=>00000,
            'name'=>$this->ddbData->character->name,
            'portrait'=>$this->ddbData->character->avatarUrl,
            'sheet_template_id'=>12,//D&D 5e
            'game_id'=>0,
            'private'=>0,
            'created_at'=>date('Y-m-d h:i:s'),
            'updated_at'=>date('Y-m-d h:i:s'),
            'deleted_at'=>null,
            'downloaded_at'=>date('Y-m-d h:i:s'),
            'sheetdata_revision_id'=>'',
            'sheet_data'=>[
                'id'=>00000,
                'sheet_id'=>00000,
                'jsondata'=>$this->data
            ]
        ];
    }

    public function exportArray()
    {
        var_dump($this->mwData);
        die();
    }

    public function exportJson()
    {
        json_encode($this->mwData);
        die();
    }

    public function exportFile()
    {
        $jsonFile = $this->filename."_for_MW_".date('Y-m-d-H-i-s').".json";

        $fp = fopen($jsonFile, 'w');
        fwrite($fp, json_encode($this->mwData));
        fclose($fp);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$jsonFile.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($jsonFile));

        flush(); // Flush system output buffer

        readfile($jsonFile);

        if (file_exists($jsonFile))
            unlink($jsonFile);
    }
}