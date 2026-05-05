<?php
namespace Avito\Export\Migration\V193;

use Avito\Export\Config;
use Avito\Export\Feed;
use Avito\Export\Migration\Patch;

class UfListDisplayOption implements Patch
{
    public function version() : string
    {
        return '1.9.3';
    }

    public function run() : void
    {
        if (
            $this->hasUserEvents() &&
            $this->hasUfListFields()
        )
        {
            Config::setOption('process_uf_list_fields', 'N');
        }
    }

    private function hasUserEvents() : bool
    {
        $events = GetModuleEvents('avito.export', 'onFeedOfferExtend', true);
        if (count($events) > 0)
        {
            return true;
        }

        $events = GetModuleEvents('avito.export', 'onFeedOfferWrite', true);
        if (count($events) > 0)
        {
            return true;
        }

        return false;
    }

    private function hasUfListFields() : bool
    {
        $feeds = Feed\Setup\RepositoryTable::getList()->fetchCollection();

        /** @var Feed\Setup\Model $feed */
        foreach ($feeds as $feed)
        {
            $tags = $feed->getTags();

            foreach ($tags as $tagCollection)
            {
                foreach($tagCollection as $tag)
                {
                    $pattern = '/\bSECTION.UF_(.*?)\b/';

                    preg_match_all($pattern, $tag['VALUE'], $matches);

                    if (empty($matches[1])) { continue; }

                    foreach ($matches[1] as $field)
                    {
                        $fieldName = 'UF_' . $field;

                        $query = \CUserTypeEntity::GetList(
                            [],
                            ['FIELD_NAME' => $fieldName]
                        );

                        while ($fieldInfo = $query->Fetch())
                        {
                            if ($fieldInfo['USER_TYPE_ID'] === 'enumeration')
                            {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

}