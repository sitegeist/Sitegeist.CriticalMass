# Sitegeist.CriticalMass
### Help managing huge amounts of nodes by automatic creation of node-hierarchies 

In Neos it is sometimes hard to handle high amounts of documents below a 
single parent node. In such cases it is hard for editors to find a 
specific document again. On top of that the performance and usability of 
the current navigate-component will suffer if too many nodes are on a 
single level.

To overcome this we suggest to use a hierarchical structure of 
collection-nodes to create a node structure that is appropriate 
for the current project and that helps editors to find documents. 
This solution has the additional benefit of representing that structure 
in the document-url aswell, that makes url-collisions much less likely.

*Since the creation of such a node hierarchy is a repetitive task we 
provide this package to help automating that.*

This package allows the configuration of node hierarchies via Eel configuration. 

A common use case would be to automatically create NewsCollection Nodes for Year and Month 
and move any News Node into a matchig collection node.

## Authors & Sponsors

* Wilhelm Behncke - behncke@sitegeist.de
* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored 
by our employer http://www.sitegeist.de.*

## Usage

```yaml
Sitegeist:
  CriticalMass:
    automaticNodeHierarchy:
    
      # The configuration for the node type Sitegeist.CriticalMass:ExampleNode     
      'Sitegeist.CriticalMass:ExampleNode':
      
        # Detect the root-collection node that will contain the automatically created node hierarchy
        root: "${q(node).parents().filter('[instanceof Sitegeist.CriticalMass:ExampleNodeCollection]').slice(-1, 1).get(0)}"
        
        # optional: Automatically publish the created document hierarchy
        autoPublishPath: true
        
        # Define the levels of the node hierarchy that are created beneath the root node
        path:
       
          # level 1 year
          -
            # the type and nodename of the hierarchy-node  
            type: 'Sitegeist.CriticalMass:ExampleNodeCollection'
            name: "${'node-event-year-' + (q(node).property('startDate') ? Date.year(q(node).property('startDate')) : 'no-year')}"
            
            # properties that are applied only on node creation and can be edited afterwards
            properties:
              title: "${q(node).property('startDate') ? Date.year(q(node).property('startDate')) : 'no-year'}"
              uriPathSegment: "${q(node).property('startDate') ? Date.year(q(node).property('startDate')) : 'no-year'}"
          
          # level 2 month
          -
            # the type and nodename of the hierarchy-node  
            type: 'Sitegeist.CriticalMass:ExampleNodeCollection'
            name: "${'node-event-month-' + (q(node).property('startDate') ? Date.month(q(node).property('startDate')) : 'no-month')}"
            
            # properties that are applied only on node creation and can be edited afterwards
            properties:
              title: "${q(node).property('startDate') ? Date.month(q(node).property('startDate')) : 'no-month'}"
              uriPathSegment: "${q(node).property('startDate') ? Date.month(q(node).property('startDate')) : 'no-month'}"
```

## Limitations 

The following issues and side effects are known at the moment:

1. Currently there is no way to notify the navigate component about a 
   needed reload. So after a node was moved behind the scene, the navigate 
   component will keep displaying the node on the current position until 
   the next reload.
2. The automatically created nodes are in the user workspace and still 
   have to be published. It is possible that this will change in the future.

## Installation

Sitegeist.CriticalMass is available via packagist. Just add `"sitegeist/criticalmass" : "~1.0"` to the require-dev section of the composer.json or run `composer require --dev sitegeist/criticalmass`. We use semantic-versioning so every breaking change will increase the major-version number.

## License

see [LICENSE file](LICENSE)
