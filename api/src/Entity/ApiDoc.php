<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *     		"get",
 *     		"post",
 *          "post_parse"={
 *     			"method"="POST",
 *     			"path"="/api_docs/parse",
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
     * @var \Ramsey\Uuid\UuidInterface The UUID identifier of this resource
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Uuid
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
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

    public function getId(): ?string
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
