<?php

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryContext;
use RemoteDataBlocks\Test\TestDatasource;
use GuzzleHttp\Psr7\Response;

class QueryContextTest extends TestCase
{
    private $datasource;
    private $queryContext;

    protected function setUp(): void
    {
        $this->datasource = new TestDatasource();
        $this->queryContext = new QueryContext($this->datasource);
    }

    public function testGetEndpoint()
    {
        $result = $this->queryContext->get_endpoint([]);
        $this->assertEquals('https://example.com', $result);
    }

    public function testGetImageUrl()
    {
        $result = $this->queryContext->get_image_url();
        $this->assertNull($result);
    }

    public function testGetMetadata()
    {
        $mockResponse = new Response(200, ['Age' => '60']);
        $results = [['id' => 1], ['id' => 2]];

        $metadata = $this->queryContext->get_metadata($mockResponse, $results);

        $this->assertArrayHasKey('last_updated', $metadata);
        $this->assertArrayHasKey('total_count', $metadata);
        $this->assertEquals('Last updated', $metadata['last_updated']['name']);
        $this->assertEquals('string', $metadata['last_updated']['type']);
        $this->assertEquals('Total count', $metadata['total_count']['name']);
        $this->assertEquals('number', $metadata['total_count']['type']);
        $this->assertEquals(2, $metadata['total_count']['value']);
    }

    public function testGetRequestMethod()
    {
        $this->assertEquals('GET', $this->queryContext->get_request_method());
    }

    public function testGetRequestHeaders()
    {
        $result = $this->queryContext->get_request_headers([]);
        $this->assertEquals(['Content-Type' => 'application/json'], $result);
    }

    public function testGetRequestBody()
    {
        $this->assertNull($this->queryContext->get_request_body([]));
    }

    public function testGetQueryName()
    {
        $this->assertEquals('Query', $this->queryContext->get_query_name());
    }

    public function testIsCollection()
    {
        $this->assertFalse($this->queryContext->is_collection());

        $this->queryContext->output_variables['is_collection'] = true;
        $this->assertTrue($this->queryContext->is_collection());
    }

    public function testDefaultProcessResponse()
    {
        $rawData = '{"key": "value"}';
        $this->assertEquals($rawData, $this->queryContext->process_response($rawData, []));
    }

    public function testCustomProcessResponse()
    {
        $customQueryContext = new class($this->datasource) extends QueryContext {
            public function process_response(string $raw_response_data, array $input_variables): string
            {
                // Convert HTML to JSON
                $dom = new \DOMDocument();
                @$dom->loadHTML($raw_response_data);
                $title = $dom->getElementsByTagName('title')->item(0)->nodeValue;
                $paragraphs = $dom->getElementsByTagName('p');
                $content = [];
                foreach ($paragraphs as $p) {
                    $content[] = $p->nodeValue;
                }
                
                $data = [
                    'title' => $title,
                    'content' => $content
                ];
                
                return json_encode($data);
            }
        };

        $htmlData = '<html><head><title>Test Page</title></head><body><p>Paragraph 1</p><p>Paragraph 2</p></body></html>';
        $expectedJson = '{"title":"Test Page","content":["Paragraph 1","Paragraph 2"]}';
        
        $this->assertEquals($expectedJson, $customQueryContext->process_response($htmlData, []));
    }
}