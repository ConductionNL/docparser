<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *     		"get",
 *     		"post",
 *          "post_parse"={
 *     			"method"="POST",
 *     			"path"="/apidocs/parse",
 *     			"swagger_context" = {
 *     				"summary"="Parse an OAS document",
 *     				"description"="Parse an OAS document"
 *     			}
 *     		}
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ApiDocsRepository")
 */
class ApiDoc
{
    /**
	 * @Groups({"read"})
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
	 * @Groups({"read","write"})
     * @ORM\Column(type="text", nullable=true)
     */
    private $docs;

    /**
	 * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocs(): ?string
    {
        return $this->docs;
    }

    public function setDocs(?string $docs): self
    {
        $this->docs = $docs;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
