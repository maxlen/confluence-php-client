<?php
declare(strict_types=1);

namespace Maxlen\ConfluenceClient\Api;

use Maxlen\ConfluenceClient\Entity\AbstractContent;
use Maxlen\ConfluenceClient\Entity\ContentSearchResult;
use Maxlen\ConfluenceClient\Entity\ContentBody;
use Maxlen\ConfluenceClient\Exception\ConfluencePhpClientException;
use Http\Client\Exception as HttpClientException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;
use function count;
use function in_array;

/**
 * @see https://docs.atlassian.com/atlassian-confluence/REST/6.6.0/#content
 */
class Content extends AbstractApi
{
    /**
     * ContentType for confluence attachments
     */
    public const CONTENT_TYPE_ATTACHMENT = 'attachment';

    /**
     * ContentType for confluence comments
     */
    public const CONTENT_TYPE_COMMENT = 'comment';

    /**
     * ContentType for confluence page content
     */
    public const CONTENT_TYPE_PAGE = 'page';

    /**
     * ContentType for confluence global content
     */
    public const CONTENT_TYPE_GLOBAL = 'global';

    /**
     * default value for expand query parameter
     */
    private const DEFAULT_EXPAND = 'space,version,body.storage,container';

    /**
     * @see https://docs.atlassian.com/atlassian-confluence/REST/6.6.0/#content-getContent
     * @param int $contentId
     * @return AbstractContent|null
     * @throws ConfluencePhpClientException
     */
    public function get(int $contentId): ?AbstractContent
    {
        $response = $this->httpGet(
            self::getRestfulUri('content', $contentId),
            ['expand' => self::DEFAULT_EXPAND]
        );
        return $this->hydrateResponse($response, AbstractContent::class);
    }


    /**
     * @see https://docs.atlassian.com/atlassian-confluence/REST/6.6.0/#content-getContent
     * @param array{title?: string, spaceKey?: string, type?: string, id?: int|string} $searchParameter
     * @return ContentSearchResult
     * @throws ConfluencePhpClientException
     */
    public function find(array $searchParameter): ContentSearchResult
    {
        $allowedSearchParameter = ['title', 'spaceKey', 'type', 'id'];
        $queryParameter = array_filter($searchParameter, static function(string $searchKey) use ($allowedSearchParameter) {
            return in_array($searchKey, $allowedSearchParameter, true);
        }, ARRAY_FILTER_USE_KEY);

        $queryParameter['expand'] = self::DEFAULT_EXPAND;

        $searchResponse = $this->httpGet('content', $queryParameter);

        return $this->hydrateResponse($searchResponse, ContentSearchResult::class);
    }

    /**
     * @see https://docs.atlassian.com/atlassian-confluence/REST/6.6.0/#content-update
     * @param AbstractContent $content
     * @return AbstractContent
     * @throws JsonException
     * @throws HttpClientException
     */
    public function update(AbstractContent $content): AbstractContent
    {
        $contentId = $content->getId();
        Assert::integer($contentId, 'The content can only be changed when it has already been created. To do this, use the "create" method.');

        $data = [
            'id' => $contentId,
            'type' => $content->getType(),
            'title' => $content->getTitle(),
            'space' => ['key' => $content->getSpace()],
            'body' => [
                'storage' => [
                    'value' => $content->getContent(),
                    'representation' => 'storage',
                ],
            ],
            'version' => ['number' => $content->getVersion() + 1]
        ];

        return $this->hydrateResponse(
            $this->httpPut(self::getRestfulUri('content', $contentId), $data),
            AbstractContent::class
        );
    }


    /**
     * @see https://docs.atlassian.com/atlassian-confluence/REST/6.6.0/#content-createContent
     * @param AbstractContent $content
     * @return AbstractContent
     * @throws ConfluencePhpClientException
     * @throws HttpClientException
     * @throws JsonException
     */
    public function create(AbstractContent $content): AbstractContent
    {
        Assert::null($content->getId(), 'Only pages not already saved can be created.');

        $data = [
            'type' => $content->getType(),
            'title' => $content->getTitle(),
            'space' => ['key' => $content->getSpace()],
            'body' => [
                'storage' => [
                    'value' => $content->getContent(),
                    'representation' => 'storage',
                ],
            ],
        ];

        if (count($content->getAncestors()) > 0) {
            $ancestorsData = array_map(static function(int $id) {
                return ['id' => $id];
            }, $content->getAncestors());

            $data['ancestors'] = $ancestorsData;
        }

        /* attach content to content */
        if (null !== $content->getContainerId()) {
            $data['container'] = [
                'id' => $content->getContainerId(),
                'type' => $content->getContainerType(),
            ];
        }

        $response = $this->httpPost(self::getRestfulUri('content'), [], $data);
        return $this->hydrateResponse($response, AbstractContent::class);

    }

    /**
     * @see https://docs.atlassian.com/atlassian-confluence/REST/6.6.0/#content-delete
     * @param AbstractContent $content
     * @return ResponseInterface
     */
    public function delete(AbstractContent $content): ResponseInterface
    {
        $contentId = $content->getId();
        Assert::integer($contentId, 'The content must already be saved to be deleted.');
        return $this->httpDelete(self::getRestfulUri('content', $contentId));
    }

    /**
     * @param AbstractContent $content
     * @param string|null $contentType
     * @return ContentSearchResult
     * @throws HttpClientException
     * @throws JsonException
     */
    public function children(AbstractContent $content, ?string $contentType = null): ContentSearchResult
    {
        return $this->hydrateResponse(
            $this->httpGet(self::getRestfulUri('content', $content->getId(), 'child', $contentType), ['expand' => self::DEFAULT_EXPAND]),
            ContentSearchResult::class
        );
    }

    /**
     * @param AbstractContent $content
     * @param string|null $contentType
     * @return ContentSearchResult
     * @throws HttpClientException
     * @throws JsonException
     */
    public function descendants(AbstractContent $content, ?string $contentType = null): ContentSearchResult
    {
        return $this->hydrateResponse(
            $this->httpGet(self::getRestfulUri('content', $content->getId(), 'descendant', $contentType)),
            ContentSearchResult::class
        );
    }

    /**
     * @see https://docs.atlassian.com/atlassian-confluence/REST/6.6.0/#contentbody/convert/{to}-convert
     * @param ContentBody $convertBody
     * @param string $to
     * @param AbstractContent|null $abstractContent
     * @return ContentBody
     */
    public function convert(ContentBody $convertBody, string $to = 'view', ?AbstractContent $abstractContent = null): ContentBody
    {
        $queryParameter = [];

        if ($abstractContent && $abstractContent->getId() !== null) {
            $queryParameter['pageIdContext'] = $abstractContent->getId();
        }

        if ($abstractContent && $abstractContent->getSpace() !== null) {
            $queryParameter['spaceKeyContext'] = $abstractContent->getSpace();
        }

        Assert::true(ContentBody::isSupported($to), 'This conversion target is not supported.');

        $data = [
            'representation' => $convertBody->getRepresentation(),
            'value' => $convertBody->getValue()
        ];

        return $this->hydrateResponse(
            $this->httpPost(
                self::getRestfulUri('contentbody', 'convert', $to),
                $queryParameter,
                $data
            ),
            ContentBody::class
        );

    }
}
